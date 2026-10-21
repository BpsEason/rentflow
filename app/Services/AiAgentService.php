<?php

namespace App\Services;

use App\Models\AiUsageLog;
use App\Models\AgentLoop;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
                'should_retry' => true,
                'retry_delay' => 86400, // 明天再試
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
你是一位資深的Laravel開發工程師，請分析以下PHP/Laravel單元測試的失敗內容，按照要求的格式提供分析。

請務必用繁體中文回答，並嚴格遵守以下結構：

1. **錯誤摘要**：簡潔說明實際發生了什麼問題
2. **可能根因**：根據錯誤訊息及堆疊追蹤提出可能的原因，必須明確區分已知事實與推測
3. **問題位置**：指出錯誤中可確認的檔案、類別、方法或行號（如果有提供）
4. **建議修正方向**：提供具體、可執行的排查或修正建議
5. **驗證方式**：指出應該執行哪些測試來確認修正有效

重要注意事項：
- 如果缺少原始程式碼，不要假裝知道實際根因
- 不要將推測描述為已確認的事實
- 保持分析客觀，基於提供的錯誤資訊進行推理

測試失敗內容：
{$testFailure}
EOT;
    }
}
