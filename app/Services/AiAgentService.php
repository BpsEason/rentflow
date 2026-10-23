<?php

namespace App\Services;

use App\Models\AiUsageLog;
use App\Models\AgentLoop;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class AiAgentService
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;
    protected int $dailyQuota;
    protected string $provider = 'gemini';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model');
        $this->baseUrl = config('services.gemini.base_url');
        $this->dailyQuota = config('services.gemini.daily_quota');
    }

    /**
     * 檢查今日是否已超過本地安全門檻
     */
    public function hasExceededQuota(): bool
    {
        $today = Carbon::today();
        $usage = AiUsageLog::where('provider', $this->provider)
            ->where('model', $this->model)
            ->where('usage_date', $today)
            ->first();

        return $usage && $usage->request_count >= $this->dailyQuota;
    }

    /**
     * 取得今日的使用量
     */
    public function getTodayUsage(): array
    {
        $today = Carbon::today();
        $usage = AiUsageLog::where('provider', $this->provider)
            ->where('model', $this->model)
            ->where('usage_date', $today)
            ->first();

        $requestCount = $usage?->request_count ?? 0;

        return [
            'request_count' => $requestCount,
            'total_tokens' => $usage?->total_tokens ?? 0,
            'quota' => $this->dailyQuota,
            // 修正：$this->dailyQuota 是屬性而非方法，並使用 max 確保剩餘額度不會變成負數
            'remaining' => max(0, $this->dailyQuota - $requestCount),
        ];
    }

    /**
     * 記錄API用量（併發安全）
     * error_details: 保存最近一次發生的錯誤，覆寫前一次的錯誤資訊（符合業務需求：只需要知道最後一次失敗原因）
     * 數值欄位使用原子increment更新，確保併發場景下不會遺失計數
     */
    public function recordUsage(int $promptTokens = 0, int $completionTokens = 0, ?string $error = null): void
    {
        $today = Carbon::today();

        AiUsageLog::updateOrCreate(
            ['provider' => $this->provider, 'model' => $this->model, 'usage_date' => $today],
            []
        );

        // 使用原子更新確保併發安全，數值欄位透過DB::raw原子累加
        AiUsageLog::where('provider', $this->provider)
            ->where('model', $this->model)
            ->where('usage_date', $today)
            ->increment('request_count', 1, [
                'prompt_tokens' => \DB::raw('prompt_tokens + ' . $promptTokens),
                'completion_tokens' => \DB::raw('completion_tokens + ' . $completionTokens),
                'total_tokens' => \DB::raw('total_tokens + ' . ($promptTokens + $completionTokens)),
                'error_details' => $error, // 只保留最後一次錯誤，覆寫行為符合業務需求
            ]);
    }

    /**
     * 分析測試失敗內容
     */
    public function analyzeTestFailure(AgentLoop $agentLoop): array
    {
        if ($this->hasExceededQuota()) {
            Log::warning('AI API quota exceeded for today', ['provider' => $this->provider, 'model' => $this->model]);
            return [
                'success' => false,
                'reason' => 'Daily quota exceeded',
                'should_retry' => false, // 配額超限不應該再重試，直接標記為失敗
                'retry_delay' => 86400,
            ];
        }

        try {
            $prompt = $this->buildAnalysisPrompt($agentLoop->test_failure_content);

            $response = Http::timeout(30)
                ->post("{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}", [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 1024,
                    ]
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $analysis = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No analysis available';
                $usageMetadata = $data['usageMetadata'] ?? [];

                $promptTokens = $usageMetadata['promptTokenCount'] ?? 0;
                $completionTokens = $usageMetadata['candidatesTokenCount'] ?? 0;

                $this->recordUsage($promptTokens, $completionTokens);

                return [
                    'success' => true,
                    'analysis' => $analysis,
                    'prompt_tokens' => $promptTokens,
                    'completion_tokens' => $completionTokens,
                ];
            }

            $statusCode = $response->status();
            $responseBody = $response->body();

            Log::error('Gemini API request failed', [
                'status' => $statusCode,
                'response' => $responseBody,
            ]);

            $this->recordUsage(0, 0, "HTTP {$statusCode}: {$responseBody}");

            if ($statusCode === 429) {
                return [
                    'success' => false,
                    'reason' => 'Rate limit exceeded (429)',
                    'should_retry' => true,
                    'retry_delay' => 3600, // 1小時後重試
                ];
            }

            if ($statusCode >= 500) {
                return [
                    'success' => false,
                    'reason' => "Server error ({$statusCode})",
                    'should_retry' => true,
                    'retry_delay' => 300, // 5分鐘後重試
                ];
            }

            if (in_array($statusCode, [400, 401, 403])) {
                return [
                    'success' => false,
                    'reason' => "Authentication/request error ({$statusCode})",
                    'should_retry' => false,
                ];
            }

            return [
                'success' => false,
                'reason' => "Unexpected error ({$statusCode})",
                'should_retry' => true,
                'retry_delay' => 600,
            ];
        } catch (\Exception $e) {
            Log::error('Exception while calling Gemini API', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->recordUsage(0, 0, "Exception: {$e->getMessage()}");

            return [
                'success' => false,
                'reason' => "Network/timeout error: {$e->getMessage()}",
                'should_retry' => true,
                'retry_delay' => 300,
            ];
        }
    }

    /**
     * 建立分析提示詞
     */
    protected function buildAnalysisPrompt(string $testFailure): string
    {
        return <<<EOT
你是一位資深的Laravel開發工程師，請分析以下PHP/Laravel單元測試的失敗內容，按照要求的JSON格式提供分析和修正方案。

請務必只回傳JSON格式，不要加入任何其他文字說明。JSON必須包含以下欄位：
{
    "error_summary": "簡潔說明實際發生了什麼問題",
    "root_cause": "根據錯誤訊息及堆疊追蹤提出可能的原因，必須明確區分已知事實與推測",
    "affected_files": ["需要修改的檔案路徑清單"],
    "fix_reason": "為什麼這樣修正能解決問題",
    "fixes": [
        {
            "file_path": "要修改的檔案絕對路徑",
            "search_code": "要搜尋替換的原始程式碼片段",
            "replace_code": "替換後的程式碼片段"
        }
    ],
    "verification": "應該執行哪些測試來確認修正有效"
}

重要注意事項：
- 如果缺少原始程式碼，不要假裝知道實際根因
- 不要將推測描述為已確認的事實
- 保持分析客觀，基於提供的錯誤資訊進行推理
- 只輸出合法的JSON格式，避免使用未轉義的引號
- search_code必須是檔案中確實存在的程式碼片段，用於精確替換

測試失敗內容：
{$testFailure}
EOT;
    }

    /**
     * 安全執行PHPUnit測試
     */
    public function runTests(array $testPaths = [], int $timeout = 120): array
    {
        $validPaths = [];

        // 加入測試檔案路徑（驗證過的安全路徑）
        foreach ($testPaths as $path) {
            if (empty($path)) {
                continue;
            }
            // 驗證路徑是否在專案內且是合法的測試檔案
            $realPath = realpath(base_path($path));
            if ($realPath && str_starts_with($realPath, base_path()) && file_exists($realPath)) {
                $validPaths[] = $realPath;
            }
        }

        // 嚴禁在未指定測試範圍時執行整套測試，避免全量測試或巢狀遞迴
        if (empty($validPaths)) {
            return [
                'success' => false,
                'exit_code' => 1,
                'output' => '',
                'error_output' => 'No valid test paths specified for test execution.',
                'summary' => ['tests' => 0, 'passed' => 0, 'failed' => 0],
                'timed_out' => false,
            ];
        }

        $command = array_merge([
            PHP_BINARY,
            'vendor/bin/phpunit',
        ], $validPaths);

        try {
            $process = \App\Support\AiAgentTestProcess::create($command, base_path());
            $process->setTimeout($timeout);
            $timedOut = false;

            try {
                $process->run();
            } catch (ProcessTimedOutException $exception) {
                $process = $exception->getProcess();
                $timedOut = true;
            }

            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();
            $exitCode = $process->getExitCode();
            $summary = $this->parseTestSummary($output . $errorOutput);

            return [
                'success' => $exitCode === 0,
                'exit_code' => $exitCode,
                'output' => $output,
                'error_output' => $errorOutput,
                'summary' => $summary,
                'timed_out' => $timedOut,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to run tests', ['message' => $e->getMessage()]);
            return [
                'success' => false,
                'exit_code' => -1,
                'output' => '',
                'error_output' => $e->getMessage(),
                'summary' => ['tests' => 0, 'passed' => 0, 'failed' => 0],
                'timed_out' => false,
                'exception' => $e->getMessage(),
            ];
        }
    }

    /**
     * 解析PHPUnit測試輸出的摘要資訊
     */
    protected function parseTestSummary(string $output): array
    {
        $tests = 0;
        $passed = 0;
        $failed = 0;

        // 匹配PHPUnit的摘要行，例如："Tests: 1, Assertions: 1, Failed: 1"
        if (preg_match('/Tests:\s*(\d+).*?Passed:\s*(\d+)/', $output, $matches)) {
            $tests = (int)$matches[1];
            $passed = (int)$matches[2];
        } elseif (preg_match('/Tests:\s*(\d+).*?Failed:\s*(\d+)/', $output, $matches)) {
            $tests = (int)$matches[1];
            $failed = (int)$matches[2];
        } elseif (preg_match('/OK\s*\((\d+)\s*tests?/', $output, $matches)) {
            $tests = (int)$matches[1];
            $passed = (int)$matches[1];
        }

        return [
            'tests' => $tests,
            'passed' => $passed,
            'failed' => $failed > 0 ? $failed : ($tests - $passed),
        ];
    }

    /**
     * 驗證修正方案是否符合安全規則
     */
    public function validateFixes(array $fixes, ?array $allowedFiles = null): array
    {
        $blockedFiles = [
            '.env',
            '.env.*',
            'composer.lock',
            'package-lock.json',
            'yarn.lock',
            'phpunit.xml',
            'phpunit.xml.dist',
        ];

        $validFixes = [];
        $errors = [];

        foreach ($fixes as $fix) {
            $filePath = $fix['file_path'] ?? '';
            $realPath = realpath(base_path($filePath));

            // 驗證檔案存在且在專案目錄內
            if (!$realPath || !str_starts_with($realPath, base_path())) {
                $errors[] = "Invalid file path: {$filePath} (outside project or not found)";
                continue;
            }

            // 驗證不在禁止修改清單中
            $fileName = basename($realPath);
            foreach ($blockedFiles as $blocked) {
                if (fnmatch($blocked, $fileName)) {
                    $errors[] = "Blocked file modification: {$filePath} (file type restricted)";
                    continue 2;
                }
            }

            // 如果有指定允許的檔案清單，驗證是否在允許清單內
            if ($allowedFiles && !in_array($realPath, array_map('realpath', $allowedFiles))) {
                $errors[] = "File not in allowed list: {$filePath}";
                continue;
            }

            // 驗證程式碼片段不包含危險操作
            $dangerousPatterns = [
                'shell_exec',
                'exec',
                'system',
                'passthru',
                'eval',
                'assert',
                'unlink',
                'rmdir',
                'mkdir',
                'file_put_contents',
                'fwrite',
                'fopen',
            ];

            foreach ($dangerousPatterns as $pattern) {
                if (stripos($fix['replace_code'] ?? '', $pattern) !== false) {
                    $errors[] = "Dangerous code detected in {$filePath}: contains {$pattern}";
                    continue 2;
                }
            }

            $validFixes[] = $fix;
        }

        return [
            'valid' => empty($errors),
            'valid_fixes' => $validFixes,
            'errors' => $errors,
        ];
    }

    /**
     * 套用修正方案，同時保存原始內容以便復原
     */
    public function applyFixes(array $validFixes): array
    {
        $patchHistory = [];
        $modifiedFiles = [];

        DB::beginTransaction();

        try {
            foreach ($validFixes as $fix) {
                $filePath = base_path($fix['file_path']);
                $originalContent = file_get_contents($filePath);

                // 保存原始內容到修補歷史
                $patchHistory[] = [
                    'file_path' => $fix['file_path'],
                    'original_content' => $originalContent,
                    'applied_at' => now()->toDateTimeString(),
                ];

                // 執行替換
                $searchCode = $fix['search_code'] ?? '';
                $replaceCode = $fix['replace_code'] ?? '';

                if (!empty($searchCode) && str_contains($originalContent, $searchCode)) {
                    $newContent = str_replace($searchCode, $replaceCode, $originalContent);
                    file_put_contents($filePath, $newContent);
                    $modifiedFiles[] = $fix['file_path'];
                } else {
                    throw new \Exception("Search code not found in {$fix['file_path']}, cannot apply fix");
                }
            }

            DB::commit();

            return [
                'success' => true,
                'patch_history' => $patchHistory,
                'modified_files' => $modifiedFiles,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            // 復原所有已修改的檔案
            foreach ($patchHistory as $patch) {
                if (file_exists(base_path($patch['file_path']))) {
                    file_put_contents(base_path($patch['file_path']), $patch['original_content']);
                }
            }

            Log::error('Failed to apply fixes', ['message' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 復原本次修補
     */
    public function revertFixes(array $patchHistory): bool
    {
        try {
            foreach ($patchHistory as $patch) {
                $filePath = base_path($patch['file_path']);
                if (file_exists($filePath)) {
                    file_put_contents($filePath, $patch['original_content']);
                    Log::info("Reverted changes to {$patch['file_path']}");
                }
            }
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to revert fixes', ['message' => $e->getMessage()]);
            return false;
        }
    }
}
