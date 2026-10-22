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
                    'processing_started_at' => now(),
                    'loop_started_at' => now(),
                ]);
        });

        // 如果更新失敗，說明已經被其他Worker處理了，直接返回
        if ($updated === 0) {
            Log::info('Agent loop already processed by another worker', ['agent_loop_id' => $this->agentLoop->id]);
            return;
        }

        // 從資料庫重新刷新模型狀態 - 必須在計算current_loop之前刷新，才能獲得最新的輪次
        $this->agentLoop->refresh();

        $maxLoops = (int) $this->agentLoop->max_loops;

        // 在執行耗時測試前，確認任務是否已超過最大迴圈次數（current_loop > max_loops 代表非法超額派發）
        // 注意：current_loop == max_loops 時是最後一輪，仍須允許執行，不可在此終止
        if ((int) $this->agentLoop->current_loop > $maxLoops) {
            DB::transaction(function () use ($maxLoops) {
                AgentLoop::where('id', $this->agentLoop->id)
                    ->where('status', 'processing')
                    ->update([
                        'status' => 'failed',
                        'failure_reason' => "Maximum loop iterations ({$maxLoops}) reached",
                        'termination_reason' => 'Max loops exceeded',
                        'processing_started_at' => null,
                    ]);
            });

            Log::error('Agent loop terminated - current_loop exceeded max_loops (illegal over-dispatch)', [
                'agent_loop_id' => $this->agentLoop->id,
                'max_loops' => $maxLoops,
                'current_loop' => $this->agentLoop->current_loop,
            ]);
            return;
        }

        // 確保目前的迴圈輪次正確（若初次執行為 0 則初始化為 1）
        $currentLoop = DB::transaction(function () {
            $agentLoop = AgentLoop::where('id', $this->agentLoop->id)
                ->where('status', 'processing')
                ->lockForUpdate()
                ->first();

            if (!$agentLoop) {
                return null;
            }

            if ($agentLoop->current_loop <= 0) {
                $agentLoop->update(['current_loop' => 1]);
            }

            return (int) $agentLoop->fresh()->current_loop;
        });

        // 如果無法獲得正確的currentLoop，說明狀態已經變更，直接返回
        if ($currentLoop === null) {
            Log::info('Agent loop status changed, cannot increment current_loop', ['agent_loop_id' => $this->agentLoop->id]);
            return;
        }

        // 記錄本輪開始
        Log::info("Starting loop iteration {$currentLoop}/{$maxLoops}", ['agent_loop_id' => $this->agentLoop->id]);

        // 步驟1：執行測試（僅在明確指定有效 test_path 時執行）
        if (!empty($this->agentLoop->test_path)) {
            $testPaths = [$this->agentLoop->test_path];
            $testResult = $aiAgentService->runTests($testPaths);

            // 保存測試結果
            $this->agentLoop->update([
                'last_test_exit_code' => $testResult['exit_code'],
                'last_test_output' => $testResult['output'] . $testResult['error_output'],
                'last_test_summary' => json_encode($testResult['summary']),
                'loop_completed_at' => now(),
                'total_execution_time' => $this->agentLoop->total_execution_time + ($this->agentLoop->loop_started_at ? $this->agentLoop->loop_started_at->diffInSeconds(now()) : 0),
            ]);

            // 步驟2：檢查測試是否通過
            if ($testResult['success']) {
                // 測試通過，標記任務完成
                DB::transaction(function () {
                    AgentLoop::where('id', $this->agentLoop->id)
                        ->where('status', 'processing')
                        ->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                            'termination_reason' => 'Tests passed successfully',
                            'processing_started_at' => null,
                        ]);
                });

                Log::info('Agent loop completed successfully - all tests passed', ['agent_loop_id' => $this->agentLoop->id]);
                return;
            }

            // 步驟4：檢查是否有測試逾時或執行錯誤
            if ($testResult['timed_out'] || $testResult['exit_code'] === -1) {
                DB::transaction(function () use ($testResult) {
                    AgentLoop::where('id', $this->agentLoop->id)
                        ->where('status', 'processing')
                        ->update([
                            'status' => 'failed',
                            'failure_reason' => $testResult['exception'] ?? 'Test process timed out or failed to execute',
                            'termination_reason' => 'Test execution failure',
                            'processing_started_at' => null,
                        ]);
                });

                Log::error('Agent loop terminated - test execution failed', [
                    'agent_loop_id' => $this->agentLoop->id,
                    'timed_out' => $testResult['timed_out'],
                    'exit_code' => $testResult['exit_code']
                ]);
                return;
            }

            // 更新失敗內容以供 AI 分析
            if (!empty($testResult['output']) || !empty($testResult['error_output'])) {
                $this->agentLoop->update([
                    'test_failure_content' => $testResult['output'] . $testResult['error_output']
                ]);
            }
        } elseif (!empty($this->agentLoop->test_failure_content)) {
            // 未指定 test_path，但已有外部傳入的失敗內容，略過執行測試，直接使用既有失敗內容分析
            Log::info('No test_path specified, using existing test_failure_content for AI analysis', [
                'agent_loop_id' => $this->agentLoop->id
            ]);
        } else {
            // 既無 test_path 也無 test_failure_content，設定錯誤並終止
            DB::transaction(function () {
                AgentLoop::where('id', $this->agentLoop->id)
                    ->where('status', 'processing')
                    ->update([
                        'status' => 'failed',
                        'failure_reason' => 'Neither test_path nor test_failure_content was provided',
                        'termination_reason' => 'Missing test input',
                        'processing_started_at' => null,
                    ]);
            });

            Log::error('Agent loop terminated - missing test path and failure content', [
                'agent_loop_id' => $this->agentLoop->id,
            ]);
            return;
        }

        // 步驟3（僅適用無 test_path 情境）：
        // 若沒有 test_path，只能執行單次分析。此時不論輪數，完成 AI 分析後即結束，
        // 不進入步驟 9 的多輪排程。本段落僅做旗標記錄，實際提前結束在步驟 6 之後處理。
        $isTextOnlyAnalysis = empty($this->agentLoop->test_path);

        // 檢查任務狀態是否仍為 processing（避免被其他 Worker 或失敗處理器異動）
        $statusCheck = AgentLoop::where('id', $this->agentLoop->id)->value('status');
        if ($statusCheck !== 'processing') {
            Log::info('Agent loop status is no longer processing, skipping AI analysis', [
                'agent_loop_id' => $this->agentLoop->id,
                'status' => $statusCheck,
            ]);
            return;
        }

        // 步驟5：測試失敗，調用AI分析並生成修正方案
        $analysisResult = $aiAgentService->analyzeTestFailure($this->agentLoop);

        if (!$analysisResult['success']) {
            // AI分析失敗，處理重試邏輯
            $newRetryCount = $this->agentLoop->retry_count + 1;

            if ($analysisResult['should_retry'] && $newRetryCount < self::MAX_BUSINESS_RETRIES) {
                $delay = $analysisResult['retry_delay'] ?? 60;
                $nextRetryAt = Carbon::now()->addSeconds($delay);

                if (str_contains($analysisResult['reason'], 'quota') || str_contains($analysisResult['reason'], '429')) {
                    AgentLoop::where('id', $this->agentLoop->id)
                        ->where('status', 'processing')
                        ->update([
                            'failure_reason' => $analysisResult['reason'],
                            'retry_count' => $newRetryCount,
                            'status' => 'waiting_quota',
                            'next_retry_at' => $nextRetryAt,
                            'processing_started_at' => null,
                            'loop_started_at' => null,
                        ]);
                    Log::warning('Agent loop entered waiting_quota state', ['agent_loop_id' => $this->agentLoop->id]);
                } else {
                    AgentLoop::where('id', $this->agentLoop->id)
                        ->where('status', 'processing')
                        ->update([
                            'failure_reason' => $analysisResult['reason'],
                            'retry_count' => $newRetryCount,
                            'next_retry_at' => $nextRetryAt,
                            'status' => 'pending',
                            'processing_started_at' => null,
                            'loop_started_at' => null,
                        ]);
                    $this->release($delay);
                    Log::warning('Agent loop will be retried', ['agent_loop_id' => $this->agentLoop->id]);
                }
            } else {
                AgentLoop::where('id', $this->agentLoop->id)
                    ->where('status', 'processing')
                    ->update([
                        'status' => 'failed',
                        'retry_count' => $newRetryCount,
                        'failure_reason' => $analysisResult['reason'],
                        'termination_reason' => 'AI analysis failed permanently',
                        'processing_started_at' => null,
                    ]);
                Log::error('Agent loop failed - AI analysis failed permanently', ['agent_loop_id' => $this->agentLoop->id]);
            }
            return;
        }

        // 步驟6：解析AI返回的JSON格式分析結果
        $aiAnalysis = json_decode($analysisResult['analysis'], true);
        $patchHistory = $this->agentLoop->patch_history ?? [];
        $modifiedFiles = $this->agentLoop->modified_files ?? [];

        // 必要欄位：JSON 必須可解析、包含 fixes 陣列，以及 error_summary 和 root_cause
        // 情況：無效 JSON、截斷、空字串 → 走重試路徑（視為暫時性解析失敗）
        // 情況：JSON 有效但缺少 fixes 鍵或缺少必要分析欄位 → 視為格式不符，標記完成（無法自動修復）
        $hasValidJson = json_last_error() === JSON_ERROR_NONE && is_array($aiAnalysis);
        $hasFixesKey = $hasValidJson && isset($aiAnalysis['fixes']) && is_array($aiAnalysis['fixes']);
        $hasRequiredFields = $hasFixesKey
            && isset($aiAnalysis['error_summary'])
            && isset($aiAnalysis['root_cause']);

        if (!$hasValidJson) {
            // JSON 無法解析（空字串、截斷、純文字）：記錄為分析失敗，但不自動重試（AI 已成功回應）
            Log::info('AI response is not valid JSON, saving raw analysis as completed', [
                'agent_loop_id' => $this->agentLoop->id,
                'raw_analysis' => $analysisResult['analysis'],
            ]);

            AgentLoop::where('id', $this->agentLoop->id)
                ->where('status', 'processing')
                ->update([
                    'ai_analysis' => $analysisResult['analysis'],
                    'provider' => 'gemini',
                    'model' => config('services.gemini.model'),
                    'status' => 'completed',
                    'completed_at' => now(),
                    'termination_reason' => 'Analysis completed without automated fixes',
                ]);
            return;
        }

        if (!$hasFixesKey || !$hasRequiredFields) {
            // JSON 有效，但缺少 fixes 鍵或必要分析欄位（error_summary/root_cause）
            // 此情況代表 AI 回傳的結構不符合預期格式，不能宣稱分析有效完成
            Log::warning('AI response JSON is missing required fields (fixes/error_summary/root_cause)', [
                'agent_loop_id' => $this->agentLoop->id,
                'has_fixes_key' => $hasFixesKey,
                'has_required_fields' => $hasRequiredFields,
            ]);

            AgentLoop::where('id', $this->agentLoop->id)
                ->where('status', 'processing')
                ->update([
                    'ai_analysis' => $analysisResult['analysis'],
                    'provider' => 'gemini',
                    'model' => config('services.gemini.model'),
                    'status' => 'failed',
                    'failure_reason' => 'AI response missing required fields: error_summary, root_cause, or fixes array',
                    'termination_reason' => 'Invalid AI response structure',
                    'processing_started_at' => null,
                ]);
            return;
        }

        // 步驟6a：明確處理 fixes 為空陣列的情況
        // isset($aiAnalysis['fixes']) 為 true 但 empty([]) 也為 true，必須獨立判斷
        if (empty($aiAnalysis['fixes'])) {
            // 保存分析結果
            $this->agentLoop->update([
                'ai_analysis' => $analysisResult['analysis'],
                'provider' => 'gemini',
                'model' => config('services.gemini.model'),
            ]);

            if (!empty($this->agentLoop->test_path)) {
                // 有真實測試但 AI 無任何修復方案：無法繼續，標記為 failed
                AgentLoop::where('id', $this->agentLoop->id)
                    ->where('status', 'processing')
                    ->update([
                        'status' => 'failed',
                        'failure_reason' => 'AI analysis provided no fix proposals to resolve the test failure',
                        'termination_reason' => 'No fixes proposed',
                        'processing_started_at' => null,
                    ]);
                Log::warning('Agent loop terminated - AI returned empty fixes for real test failure', [
                    'agent_loop_id' => $this->agentLoop->id,
                    'current_loop' => $currentLoop,
                ]);
            } else {
                // 無 test_path 的文字分析，AI 無建議修復：分析已完成，標記 completed
                AgentLoop::where('id', $this->agentLoop->id)
                    ->where('status', 'processing')
                    ->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'termination_reason' => 'Analysis completed with no fixes (text-only input, no test_path)',
                        'processing_started_at' => null,
                    ]);
                Log::info('Agent loop completed - AI returned empty fixes for text-only analysis', [
                    'agent_loop_id' => $this->agentLoop->id,
                ]);
            }
            return;
        }

        // 保存AI分析結果
        $this->agentLoop->update([
            'ai_analysis' => $analysisResult['analysis'],
            'provider' => 'gemini',
            'model' => config('services.gemini.model'),
        ]);

        // 步驟7：驗證修正方案是否符合安全規則
        $validationResult = $aiAgentService->validateFixes($aiAnalysis['fixes'], $this->agentLoop->allowed_files);

        if (!$validationResult['valid']) {
            Log::error('Fix validation failed', [
                'agent_loop_id' => $this->agentLoop->id,
                'errors' => $validationResult['errors']
            ]);

            // 修正方案無效，結束任務
            AgentLoop::where('id', $this->agentLoop->id)
                ->where('status', 'processing')
                ->update([
                    'status' => 'failed',
                    'failure_reason' => 'Fix validation failed: ' . implode(', ', $validationResult['errors']),
                    'termination_reason' => 'Invalid fix proposals',
                    'processing_started_at' => null,
                ]);
            return;
        }

        // 步驟8：如果允許自動套用修正，則套用
        if ($this->agentLoop->auto_apply_fixes) {
            $applyResult = $aiAgentService->applyFixes($validationResult['valid_fixes']);

            if ($applyResult['success']) {
                // 合併修補歷史和修改的檔案清單
                $patchHistory = array_merge($patchHistory, $applyResult['patch_history']);
                $modifiedFiles = array_merge($modifiedFiles, $applyResult['modified_files']);

                $this->agentLoop->update([
                    'patch_history' => $patchHistory,
                    'modified_files' => $modifiedFiles,
                ]);

                Log::info('Successfully applied fixes', [
                    'agent_loop_id' => $this->agentLoop->id,
                    'modified_files' => $applyResult['modified_files']
                ]);
            } else {
                // 套用失敗，記錄錯誤但繼續下一輪
                Log::error('Failed to apply fixes', [
                    'agent_loop_id' => $this->agentLoop->id,
                    'error' => $applyResult['error']
                ]);
            }
        } else {
            // 不自動套用，僅記錄建議的修正方案
            Log::info('Auto-apply disabled, fix proposals saved but not applied', [
                'agent_loop_id' => $this->agentLoop->id,
                'proposed_fixes' => $validationResult['valid_fixes']
            ]);
        }

        // 步驟9：判斷是否可進行下一輪
        // 三個條件必須同時滿足才能排程下一輪：
        // 1. 有真實 test_path（無測試路徑時僅做單次分析，不能反覆驗證修復）
        // 2. 有非空的修復方案（步驟 6a 已確保到此點 fixes 非空）
        // 3. 當前輪次未達最大輪數
        if ($isTextOnlyAnalysis) {
            // 無 test_path：AI 提供了修復建議，但無測試可驗證，保存分析結果並標記已完成（未驗證）
            AgentLoop::where('id', $this->agentLoop->id)
                ->where('status', 'processing')
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'termination_reason' => 'Analysis completed with proposed fixes (unverified, no test_path)',
                    'processing_started_at' => null,
                ]);
            Log::info('Agent loop completed - fixes proposed but unverified (no test_path)', [
                'agent_loop_id' => $this->agentLoop->id,
                'fix_count' => count($aiAnalysis['fixes']),
            ]);
            return;
        }

        if ($currentLoop >= $maxLoops) {
            // 已達最大輪數，本輪已執行測試與分析，不再派送下一輪
            DB::transaction(function () use ($maxLoops) {
                AgentLoop::where('id', $this->agentLoop->id)
                    ->where('status', 'processing')
                    ->update([
                        'status' => 'failed',
                        'failure_reason' => "Maximum loop iterations ({$maxLoops}) reached",
                        'termination_reason' => 'Max loops exceeded',
                        'processing_started_at' => null,
                    ]);
            });
            Log::error('Agent loop terminated - max loops reached after completing current loop', [
                'agent_loop_id' => $this->agentLoop->id,
                'max_loops' => $maxLoops,
                'current_loop' => $currentLoop,
            ]);
            return;
        }

        // 準備下一輪，重新派送任務
        $nextLoop = $currentLoop + 1;
        $updated = AgentLoop::where('id', $this->agentLoop->id)
            ->where('status', 'processing')
            ->update([
                'status' => 'pending',
                'current_loop' => $nextLoop,
                'retry_count' => 0,
                'processing_started_at' => null,
                'loop_started_at' => null,
                'next_retry_at' => now()->addSeconds(10), // 10秒後開始下一輪
            ]);

        if ($updated > 0) {
            // 只有更新成功才派送下一輪，並延遲10秒派送，與next_retry_at保持一致
            $dispatch = ProcessAgentLoop::dispatch($this->agentLoop->fresh())
                ->delay(now()->addSeconds(10));

            // 當系統預設佇列為 sync 且非 Queue::fake 測試時，若有配置異步 database 佇列，則導向 database 避免在同一個呼叫堆疊中同步無限遞迴
            if (config('queue.default') === 'sync' && config('queue.connections.database') && !(\Illuminate\Support\Facades\Queue::getFacadeRoot() instanceof \Illuminate\Support\Testing\Fakes\QueueFake)) {
                $dispatch->onConnection('database');
            }

            Log::info("Loop {$currentLoop} completed, next iteration scheduled in 10 seconds", ['agent_loop_id' => $this->agentLoop->id]);
        } else {
            // 更新失敗，無法派送下一輪
            Log::warning("Failed to update agent loop status to pending, skipping next iteration dispatch", ['agent_loop_id' => $this->agentLoop->id]);
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
