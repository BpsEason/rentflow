<?php

namespace App\Jobs;

use App\Models\AgentLoop;
use App\Services\AiAgentService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessAgentLoop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public AgentLoop $agentLoop;
    public $tries = 6; // Laravel Queue 重試次數，比業務最大重試次數多1，確保業務邏輯主導重試
    public $timeout = 60; // API 呼叫超時保護
    public $maxExceptions = 6; // 與$tries保持一致，避免Laravel提前丟棄任務
    public const MAX_BUSINESS_RETRIES = 5; // 業務層最大重試次數

    /**
     * Create a new job instance.
     */
    public function __construct(AgentLoop $agentLoop)
    {
        $this->agentLoop = $agentLoop;
    }

    /**
     * Execute the job.
     */
    public function handle(AiAgentService $aiAgentService): void
    {
        // 使用短交易確保lockForUpdate()的生命週期正確，僅包住狀態搶占邏輯
        $updated = DB::transaction(function () {
            return AgentLoop::where('id', $this->agentLoop->id)
                ->whereIn('status', ['pending', 'waiting_quota'])
                ->lockForUpdate()
                ->update([
                    'status' => 'processing',
                    'processing_started_at' => now()
                ]);
        });

        // 如果更新失敗，說明已經被其他Worker處理了，直接返回
        if ($updated === 0) {
            Log::info('Agent loop already processed by another worker', ['agent_loop_id' => $this->agentLoop->id]);
            return;
        }

        // 從資料庫重新刷新模型狀態
        $this->agentLoop->refresh();

        $result = $aiAgentService->analyzeTestFailure($this->agentLoop);

        // 再次檢查當前狀態是否仍是processing，避免舊Worker覆寫已完成的任務
        $currentAgentLoop = AgentLoop::find($this->agentLoop->id);
        if (!in_array($currentAgentLoop->status, ['processing'])) {
            Log::info('Agent loop already in terminal state, skipping updates', [
                'agent_loop_id' => $this->agentLoop->id,
                'current_status' => $currentAgentLoop->status
            ]);
            return;
        }

        if ($result['success']) {
            // 使用條件式更新，只有狀態仍是processing才能更新為completed
            $updated = AgentLoop::where('id', $this->agentLoop->id)
                ->where('status', 'processing')
                ->update([
                    'ai_analysis' => $result['analysis'],
                    'status' => 'completed',
                    'completed_at' => now(),
                    'provider' => 'gemini',
                    'model' => config('services.gemini.model'),
                ]);

            if ($updated > 0) {
                Log::info('Agent loop processing completed', ['agent_loop_id' => $this->agentLoop->id]);
            } else {
                Log::warning('Failed to mark agent loop as completed, status was not processing', ['agent_loop_id' => $this->agentLoop->id]);
            }
            return;
        }

        // 處理失敗情況，先遞增重試次數
        $newRetryCount = $this->agentLoop->retry_count + 1;

        // 判斷是否需要重試：業務層允許重試 且 未超過最大業務重試次數
        if ($result['should_retry'] && $newRetryCount < self::MAX_BUSINESS_RETRIES) {
            $delay = $result['retry_delay'] ?? 60;
            $nextRetryAt = Carbon::now()->addSeconds($delay);

            // 如果是配額問題，設定狀態為waiting_quota，由定時掃描任務恢復
            if (str_contains($result['reason'], 'quota') || str_contains($result['reason'], '429')) {
                // 條件式更新，只有狀態仍是processing才能更新
                $updated = AgentLoop::where('id', $this->agentLoop->id)
                    ->where('status', 'processing')
                    ->update([
                        'failure_reason' => $result['reason'],
                        'retry_count' => $newRetryCount,
                        'status' => 'waiting_quota',
                        'next_retry_at' => $nextRetryAt,
                        'processing_started_at' => null,
                    ]);

                if ($updated > 0) {
                    Log::warning('Agent loop entered waiting_quota state, will be recovered by scheduler', [
                        'agent_loop_id' => $this->agentLoop->id,
                        'retry_count' => $newRetryCount,
                        'next_retry_at' => $nextRetryAt,
                        'reason' => $result['reason'],
                    ]);
                } else {
                    Log::warning('Failed to update agent loop to waiting_quota, status was not processing', ['agent_loop_id' => $this->agentLoop->id]);
                }
            } else {
                // 其他錯誤使用Laravel Queue的release()機制，讓Queue負責重試排程
                $updated = AgentLoop::where('id', $this->agentLoop->id)
                    ->where('status', 'processing')
                    ->update([
                        'failure_reason' => $result['reason'],
                        'retry_count' => $newRetryCount,
                        'next_retry_at' => $nextRetryAt,
                        'status' => 'pending', // 重置為pending，讓下一次重試能被處理
                        'processing_started_at' => null,
                    ]);

                if ($updated > 0) {
                    $this->release($delay);
                    Log::warning('Agent loop will be retried by queue', [
                        'agent_loop_id' => $this->agentLoop->id,
                        'retry_count' => $newRetryCount,
                        'retry_delay' => $delay,
                        'reason' => $result['reason'],
                    ]);
                } else {
                    Log::warning('Failed to update agent loop to pending for retry, status was not processing', ['agent_loop_id' => $this->agentLoop->id]);
                }
            }
        } else {
            // 超過重試次數或不應重試，標記為失敗
            $updated = AgentLoop::where('id', $this->agentLoop->id)
                ->where('status', 'processing')
                ->update([
                    'failure_reason' => $result['reason'],
                    'retry_count' => $newRetryCount,
                    'status' => 'failed',
                    'processing_started_at' => null,
                ]);

            if ($updated > 0) {
                Log::error('Agent loop processing failed permanently', [
                    'agent_loop_id' => $this->agentLoop->id,
                    'retry_count' => $newRetryCount,
                    'reason' => $result['reason'],
                ]);
            } else {
                Log::warning('Failed to mark agent loop as failed, status was not processing', ['agent_loop_id' => $this->agentLoop->id]);
            }
        }
    }

    /**
     * 處理任務失敗（Laravel Queue自動呼叫）
     * 當Queue重試次數耗盡時，確保不會留下永久processing狀態的任務
     */
    public function failed(\Throwable $exception): void
    {
        // 加入終態保護，只有非終態任務才會被處理
        DB::transaction(function () use ($exception) {
            $agentLoop = AgentLoop::where('id', $this->agentLoop->id)
                ->whereIn('status', ['processing', 'pending']) // 只處理可能的卡住狀態，排除completed/failed等終態
                ->lockForUpdate()
                ->first();

            if ($agentLoop) {
                $newRetryCount = $agentLoop->retry_count + 1;

                // 若仍有業務重試次數，重置為pending讓恢復機制處理
                if ($newRetryCount < self::MAX_BUSINESS_RETRIES) {
                    // 使用條件式更新，確保只有狀態仍符合條件才更新
                    $updated = AgentLoop::where('id', $this->agentLoop->id)
                        ->whereIn('status', ['processing', 'pending'])
                        ->update([
                            'status' => 'pending',
                            'retry_count' => $newRetryCount,
                            'failure_reason' => 'Queue worker exception: ' . $exception->getMessage(),
                            'next_retry_at' => now()->addSeconds(60),
                            'processing_started_at' => null, // 重置處理時間
                        ]);

                    if ($updated > 0) {
                        Log::warning('Agent loop queue exhausted, will be recovered by scheduler', [
                            'agent_loop_id' => $this->agentLoop->id,
                            'retry_count' => $newRetryCount,
                            'exception' => $exception->getMessage(),
                        ]);
                    } else {
                        Log::info('Agent loop already updated by another process, skipping failed() handling', [
                            'agent_loop_id' => $this->agentLoop->id
                        ]);
                    }
                } else {
                    // 已達業務最大重試次數，標記為永久失敗
                    $updated = AgentLoop::where('id', $this->agentLoop->id)
                        ->whereIn('status', ['processing', 'pending'])
                        ->update([
                            'status' => 'failed',
                            'failure_reason' => 'Permanent failure after queue exhaustion: ' . $exception->getMessage(),
                            'processing_started_at' => null,
                        ]);

                    if ($updated > 0) {
                        Log::error('Agent loop permanently failed after queue exhaustion', [
                            'agent_loop_id' => $this->agentLoop->id,
                            'exception' => $exception->getMessage(),
                        ]);
                    } else {
                        Log::info('Agent loop already in terminal state, skipping permanent failure marking', [
                            'agent_loop_id' => $this->agentLoop->id
                        ]);
                    }
                }
            } else {
                Log::info('Agent loop not found or already in terminal state, failed() handler skipped', [
                    'agent_loop_id' => $this->agentLoop->id
                ]);
            }
        });
    }
}
