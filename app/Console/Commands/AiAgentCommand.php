<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAgentLoop;
use App\Models\AgentLoop;
use App\Services\AiAgentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Support\AiAgentTestProcess;
use Illuminate\Support\Facades\Log;

class AiAgentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:agent 
        {action : start|usage|recover|test}
        {--file= : Path to test failure log file (for start action)}
        {--content= : Direct test failure content string (for start action)}
        {--test-path= : Test file/path to execute (for start/loop action)}
        {--max-loops=3 : Maximum loop iterations (default: 3)}
        {--auto-apply-fixes : Automatically apply validated fixes (default: false)}
        {--execution-timeout=3600 : Total execution time limit in seconds (default: 3600)}
        {--allowed-files=* : Comma-separated list of allowed files to modify (optional)}
        {--filter= : Filter tests by name (for test action)}
        {--group= : Filter tests by group (for test action)}
        {--testdox : Output testdox format (for test action)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'AI Agent Loop commands - start analysis or check usage';

    /**
     * Execute the console command.
     */
    public function handle(AiAgentService $aiAgentService)
    {
        $action = $this->argument('action');

        match ($action) {
            'start' => $this->startAnalysis(),
            'usage' => $this->showUsage($aiAgentService),
            'recover' => $this->recoverWaitingQuotaJobs(),
            'test' => $exitCode = $this->runTestsAndAnalyzeFailures(),
            default => $this->error('Invalid action. Use "start", "usage", "recover" or "test".'),
        };

        return $exitCode ?? 0;

        return 0;
    }

    /**
     * 恢復所有需要恢復的任務：
     * 1. 已到達 next_retry_at 的 waiting_quota 任務
     * 2. processing狀態且超時（超過5分鐘）的卡住任務
     * 3. pending狀態但next_retry_at已到期的派送失敗任務
     * 使用原子更新確保多程序同時執行時不會重複派送同一任務
     */
    protected function recoverWaitingQuotaJobs(): void
    {
        $count = 0;
        $timeoutThreshold = now()->subMinutes(5); // 處理超過5分鐘的processing任務

        // 使用單一原子更新來擷取所有符合條件的任務，避免先查後更新的競爭窗口
        // 先使用原子更新鎖定所有符合條件的任務，一次更新所有符合條件的記錄
        $recoveredIds = collect();

        DB::transaction(function () use ($timeoutThreshold, &$recoveredIds) {
            // 先查詢所有符合條件且尚未被其他程序處理的任務
            $candidates = AgentLoop::where(function ($query) use ($timeoutThreshold) {
                $query->where(function ($q) {
                    $q->where('status', 'waiting_quota')
                        ->where('next_retry_at', '<=', now());
                })->orWhere(function ($q) use ($timeoutThreshold) {
                    $q->where('status', 'processing')
                        ->where('processing_started_at', '<=', $timeoutThreshold);
                })->orWhere(function ($q) {
                    $q->where('status', 'pending')
                        ->where(function ($pending) {
                            $pending->where(function ($subQ) {
                                $subQ->whereNotNull('next_retry_at')
                                    ->where('next_retry_at', '<=', now());
                            })->orWhere(function ($subQ) {
                                $subQ->whereNull('next_retry_at')
                                    ->where('created_at', '<=', now()->subSeconds(60)); // 超過1分鐘的舊pending任務，即使next_retry_at是null也恢復
                            });
                        })
                        ->where('created_at', '<=', now()->subSeconds(30)); // 新增保護：確保任務至少存在30秒才會被恢復，避免派送中的新任務被誤判
                });
            })
                ->lockForUpdate() // 在交易中使用悲觀鎖，避免並發問題
                ->get();

            if ($candidates->isEmpty()) {
                return;
            }

            // 對這些任務進行原子狀態更新，並收集ID
            foreach ($candidates as $candidate) {
                $updated = AgentLoop::where('id', $candidate->id)
                    ->where(function ($query) use ($timeoutThreshold) {
                        $query->where(function ($q) {
                            $q->where('status', 'waiting_quota')
                                ->where('next_retry_at', '<=', now());
                        })->orWhere(function ($q) use ($timeoutThreshold) {
                            $q->where('status', 'processing')
                                ->where('processing_started_at', '<=', $timeoutThreshold);
                        })->orWhere(function ($q) {
                            $q->where('status', 'pending')
                                ->where(function ($pending) {
                                    $pending->where(function ($subQ) {
                                        $subQ->whereNotNull('next_retry_at')
                                            ->where('next_retry_at', '<=', now());
                                    })->orWhere(function ($subQ) {
                                        $subQ->whereNull('next_retry_at')
                                            ->where('created_at', '<=', now()->subSeconds(60));
                                    });
                                })
                                ->where('created_at', '<=', now()->subSeconds(30));
                        });
                    })
                    ->update([
                        'status' => 'pending',
                        'processing_started_at' => null, // 重置processing開始時間
                        'next_retry_at' => null // 清除next_retry_at，避免再次被恢復機制選中
                    ]);

                if ($updated > 0) {
                    $recoveredIds->push($candidate->id);
                }
            }
        });

        if ($recoveredIds->isEmpty()) {
            $this->line('No jobs to recover.');
            return;
        }

        // 只有成功更新狀態的任務才會被派送
        foreach ($recoveredIds as $id) {
            $agentLoop = AgentLoop::find($id);
            if ($agentLoop && $agentLoop->status === 'pending') {
                // 任務已成功恢復，立即派送（因為已經通過條件檢查過next_retry_at已到期）
                ProcessAgentLoop::dispatch($agentLoop);
                $count++;
                $this->line("Recovered agent loop ID: {$id}");
            }
        }

        $this->info("Successfully recovered {$count} jobs.");
    }

    /**
     * Start a new AI analysis
     */
    protected function startAnalysis(): void
    {
        $content = null;

        if ($this->option('file')) {
            $filePath = $this->option('file');
            if (!file_exists($filePath)) {
                $this->error("File not found: {$filePath}");
                return;
            }
            $content = file_get_contents($filePath);
        } elseif ($this->option('content')) {
            $content = $this->option('content');
        } elseif ($this->option('test-path')) {
            // 如果指定了test-path，先執行測試獲取失敗內容
            $this->info('Test path provided, will execute tests first in the loop.');
            $content = 'Will execute tests automatically in loop: ' . $this->option('test-path');
        } else {
            $this->error('Either --file, --content, or --test-path must be provided for start action.');
            $this->line('Example: php artisan ai:agent start --file=/path/to/test-failures.log');
            $this->line('Example: php artisan ai:agent start --content="Test failed: SQL error..."');
            $this->line('Example: php artisan ai:agent start --test-path=tests/Feature/ExampleTest.php --max-loops=5 --auto-apply-fixes');
            return;
        }

        if (empty($content)) {
            $this->error('Test failure content is empty.');
            return;
        }

        // 處理允許修改的檔案清單
        $allowedFiles = null;
        $optionAllowed = (array) $this->option('allowed-files');
        if (!empty($optionAllowed) && $optionAllowed !== ['*']) {
            $allowedFiles = [];
            foreach ($optionAllowed as $item) {
                foreach (explode(',', (string) $item) as $file) {
                    $trimmed = trim($file);
                    if (!empty($trimmed) && $trimmed !== '*') {
                        $allowedFiles[] = $trimmed;
                    }
                }
            }
            if (empty($allowedFiles)) {
                $allowedFiles = null;
            }
        }

        // 建立新的AgentLoop記錄，包含所有迴圈參數
        $agentLoopData = [
            'test_failure_content' => $content,
            'next_retry_at' => now()->addSeconds(30), // 預先設定next_retry_at，確保即使派送失敗也能被恢復
            'current_loop' => 0,
            'max_loops' => (int)$this->option('max-loops'),
            'test_path' => $this->option('test-path'),
            'auto_apply_fixes' => $this->option('auto-apply-fixes'),
            'total_execution_time' => 0,
            'allowed_files' => $allowedFiles,
            'loop_started_at' => null,
            'loop_completed_at' => null,
        ];

        $agentLoop = AgentLoop::create($agentLoopData);
        $this->logAgentLoopDispatchContext($agentLoop);

        $this->info("Created new agent loop with ID: {$agentLoop->id}");
        $this->line("Loop configuration: max_loops={$agentLoop->max_loops}, auto_apply=" . ($agentLoop->auto_apply_fixes ? 'true' : 'false') . ', test_path=' . ($agentLoop->test_path ?? 'none'));

        // dispatch到隊列
        ProcessAgentLoop::dispatch($agentLoop);

        $this->info('Job dispatched to queue. Processing will begin shortly.');
        $this->line('Check status with: SELECT * FROM agent_loops WHERE id = ' . $agentLoop->id);
    }

    /**
     * Show current AI usage statistics
     */
    protected function showUsage(AiAgentService $aiAgentService): void
    {
        $usage = $aiAgentService->getTodayUsage();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Today\'s Requests', $usage['request_count'] . ' / ' . $usage['quota']],
                ['Remaining Requests', $usage['remaining']],
                ['Total Tokens Used', $usage['total_tokens']],
                ['Provider', 'gemini'],
                ['Model', config('services.gemini.model')],
            ]
        );

        // 顯示所有歷史記錄
        $this->newLine();
        $this->info('Historical Usage:');

        $history = \App\Models\AiUsageLog::orderBy('usage_date', 'desc')
            ->get(['usage_date', 'request_count', 'total_tokens', 'model'])
            ->map(fn($log) => [
                $log->usage_date->format('Y-m-d'),
                $log->request_count,
                $log->total_tokens,
                $log->model,
            ]);

        if ($history->isNotEmpty()) {
            $this->table(
                ['Date', 'Requests', 'Total Tokens', 'Model'],
                $history
            );
        } else {
            $this->line('No historical usage data found.');
        }

        // 顯示所有agent loop狀態
        $this->newLine();
        $this->info('Agent Loop Status Summary:');

        $statusSummary = AgentLoop::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get();

        $this->table(
            ['Status', 'Count'],
            $statusSummary->map(fn($s) => [$s->status, $s->count])
        );
    }

    /**
     * 執行PHPUnit測試並分析失敗內容
     */
    protected function runTestsAndAnalyzeFailures(): int
    {
        // 建構PHPUnit指令
        $command = [
            PHP_BINARY,
            'vendor/bin/phpunit',
        ];

        // 加入過濾參數
        if ($this->option('filter')) {
            $command[] = '--filter';
            $command[] = $this->option('filter');
        }

        if ($this->option('group')) {
            $command[] = '--group';
            $command[] = $this->option('group');
        }

        if ($this->option('testdox')) {
            $command[] = '--testdox';
        }

        $this->info('Executing tests with command: ' . implode(' ', $command));
        $this->newLine();

        // 建立並執行程序
        try {
            $process = AiAgentTestProcess::create($command, base_path());
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return 1;
        }
        $process->setTimeout(3600); // 最長等待1小時

        try {
            // 即時輸出測試結果到控制台
            $process->run(function ($type, $buffer) {
                $this->line($buffer);
            });

            // 獲取完整輸出
            $output = $process->getOutput() . $process->getErrorOutput();
            $exitCode = $process->getExitCode();

            // 如果測試失敗（退出碼非0），收集失敗內容並建立分析任務
            if ($exitCode !== 0) {
                $this->newLine();
                $this->warn("Tests failed with exit code: {$exitCode}");
                $this->info('Collecting failure content for AI analysis...');

                // 提取失敗相關內容，限制大小以控制token使用
                $truncatedOutput = $this->extractRelevantFailureContent($output);

                if (!empty($truncatedOutput)) {
                    // 處理允許修改的檔案清單
                    $allowedFiles = null;
                    $optionAllowed = (array) $this->option('allowed-files');
                    if (!empty($optionAllowed) && $optionAllowed !== ['*']) {
                        $allowedFiles = [];
                        foreach ($optionAllowed as $item) {
                            foreach (explode(',', (string) $item) as $file) {
                                $trimmed = trim($file);
                                if (!empty($trimmed) && $trimmed !== '*') {
                                    $allowedFiles[] = $trimmed;
                                }
                            }
                        }
                        if (empty($allowedFiles)) {
                            $allowedFiles = null;
                        }
                    }

                    // 建立新的AgentLoop記錄，包含所有迴圈參數
                    $agentLoopData = [
                        'test_failure_content' => $truncatedOutput,
                        'next_retry_at' => now()->addSeconds(30),
                        'current_loop' => 0,
                        'max_loops' => (int)$this->option('max-loops'),
                        'test_path' => $this->option('test-path') ?: null,
                        'auto_apply_fixes' => $this->option('auto-apply-fixes'),
                        'total_execution_time' => 0,
                        'allowed_files' => $allowedFiles,
                        'loop_started_at' => null,
                        'loop_completed_at' => null,
                    ];

                    $agentLoop = AgentLoop::create($agentLoopData);
                    $this->logAgentLoopDispatchContext($agentLoop);

                    $this->info("Created new agent loop with ID: {$agentLoop->id}");
                    $this->line("Loop configuration: max_loops={$agentLoop->max_loops}, auto_apply=" . ($agentLoop->auto_apply_fixes ? 'true' : 'false'));

                    // 分派到隊列
                    ProcessAgentLoop::dispatch($agentLoop);

                    $this->info('AI analysis and loop job dispatched to queue. Check status in agent_loops table.');
                }
            } else {
                $this->newLine();
                $this->info('✅ All tests passed! No analysis needed.');
            }

            return $exitCode;
        } catch (ProcessFailedException $e) {
            $this->error('Failed to execute test process: ' . $e->getMessage());
            return 1;
        } catch (\Exception $e) {
            $this->error('Unexpected error while running tests: ' . $e->getMessage());
            return 1;
        }
    }

    private function logAgentLoopDispatchContext(AgentLoop $agentLoop): void
    {
        $connection = $agentLoop->getConnection();
        Log::info('Dispatching AI agent loop', [
            'agent_loop_id' => $agentLoop->getKey(),
            'connection' => $agentLoop->getConnectionName(),
            'database' => $connection->getDatabaseName(),
            'exists_after_create' => AgentLoop::on($agentLoop->getConnectionName())
                ->whereKey($agentLoop->getKey())->exists(),
            'exists_before_dispatch' => AgentLoop::on($agentLoop->getConnectionName())
                ->whereKey($agentLoop->getKey())->exists(),
            'queue_connection' => config('queue.default'),
        ]);
    }

    /**
     * 從完整測試輸出中提取相關的失敗內容，控制輸入大小
     */
    protected function extractRelevantFailureContent(string $fullOutput): string
    {
        // 尋找失敗的測試區段
        $lines = explode("\n", $fullOutput);
        $relevantLines = [];
        $capturing = false;
        $failureCount = 0;
        $maxFailures = 5; // 最多處理前5個失敗
        $maxContentLength = 15000; // 大約15k字元，控制token數量

        foreach ($lines as $line) {
            // 檢測到失敗開始
            if (str_contains($line, 'FAIL') || str_contains($line, 'Failure') || str_contains($line, 'Error:')) {
                if ($failureCount >= $maxFailures) {
                    continue;
                }
                $capturing = true;
                $failureCount++;
                $relevantLines[] = "=== Failure #{$failureCount} ===";
            }

            // 繼續捕獲直到遇到下一個主要區段或堆疊結束
            if ($capturing) {
                $relevantLines[] = $line;

                // 當捕獲到足夠內容或到達測試摘要時停止
                if (
                    str_contains($line, 'Tests:') && str_contains($line, 'passed') ||
                    str_contains($line, 'OK.') ||
                    strlen(implode("\n", $relevantLines)) >= $maxContentLength
                ) {
                    $capturing = false;
                    if (strlen(implode("\n", $relevantLines)) >= $maxContentLength) {
                        $relevantLines[] = "\n[... output truncated due to size limits ...]";
                    }
                    break;
                }
            }
        }

        // 如果沒有捕獲到結構化的失敗，直接傳回截斷的完整輸出
        if (empty($relevantLines)) {
            $truncated = substr($fullOutput, 0, $maxContentLength);
            if (strlen($fullOutput) > $maxContentLength) {
                $truncated .= "\n[... output truncated ...]";
            }
            return $truncated;
        }

        return implode("\n", $relevantLines);
    }
}
