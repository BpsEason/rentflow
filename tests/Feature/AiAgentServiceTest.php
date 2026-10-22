<?php

namespace Tests\Feature;

use App\Models\AiUsageLog;
use App\Models\AgentLoop;
use App\Services\AiAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AiAgentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected string $geminiModel;
    protected string $geminiBaseUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->geminiModel = config('services.gemini.model');
        $this->geminiBaseUrl = config('services.gemini.base_url');
    }

    #[Test]
    public function it_correctly_records_usage_when_api_call_succeeds()
    {
        Http::fake([
            "{$this->geminiBaseUrl}/models/{$this->geminiModel}:generateContent*" => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => '分析結果：測試失敗是因為資料庫連線問題']]]]
                ],
                'usageMetadata' => [
                    'promptTokenCount' => 100,
                    'candidatesTokenCount' => 50,
                ]
            ], 200)
        ]);

        $agentLoop = AgentLoop::create([
            'test_failure_content' => 'Test failed: SQLSTATE[HY000] [2002] Connection refused',
        ]);

        $service = app(AiAgentService::class);
        $result = $service->analyzeTestFailure($agentLoop);

        $this->assertTrue($result['success']);
        $this->assertEquals(100, $result['prompt_tokens']);
        $this->assertEquals(50, $result['completion_tokens']);

        // 驗證用量記錄正確
        $usageLog = AiUsageLog::first();
        $this->assertNotNull($usageLog);
        $this->assertEquals(1, $usageLog->request_count);
        $this->assertEquals(100, $usageLog->prompt_tokens);
        $this->assertEquals(50, $usageLog->completion_tokens);
        $this->assertEquals(150, $usageLog->total_tokens);
    }

    #[Test]
    public function it_handles_429_rate_limit_correctly()
    {
        Http::fake([
            "{$this->geminiBaseUrl}/models/{$this->geminiModel}:generateContent*" => Http::response([
                'error' => ['message' => 'Rate limit exceeded']
            ], 429)
        ]);

        $agentLoop = AgentLoop::create([
            'test_failure_content' => 'Test failed...',
        ]);

        $service = app(AiAgentService::class);
        $result = $service->analyzeTestFailure($agentLoop);

        $this->assertFalse($result['success']);
        $this->assertTrue($result['should_retry']);
        $this->assertEquals(3600, $result['retry_delay']); // 1小時
        $this->assertStringContainsString('429', $result['reason']);
    }

    #[Test]
    public function it_handles_500_server_error_correctly()
    {
        Http::fake([
            "{$this->geminiBaseUrl}/models/{$this->geminiModel}:generateContent*" => Http::response([], 500)
        ]);

        $agentLoop = AgentLoop::create([
            'test_failure_content' => 'Test failed...',
        ]);

        $service = app(AiAgentService::class);
        $result = $service->analyzeTestFailure($agentLoop);

        $this->assertFalse($result['success']);
        $this->assertTrue($result['should_retry']);
        $this->assertEquals(300, $result['retry_delay']); // 5分鐘
    }

    #[Test]
    public function it_does_not_retry_on_401_authentication_error()
    {
        Http::fake([
            "{$this->geminiBaseUrl}/models/{$this->geminiModel}:generateContent*" => Http::response([], 401)
        ]);

        $agentLoop = AgentLoop::create([
            'test_failure_content' => 'Test failed...',
        ]);

        $service = app(AiAgentService::class);
        $result = $service->analyzeTestFailure($agentLoop);

        $this->assertFalse($result['success']);
        $this->assertFalse($result['should_retry']);
    }

    #[Test]
    public function it_blocks_calls_when_quota_is_exceeded()
    {
        Http::fake([
            "{$this->geminiBaseUrl}/models/{$this->geminiModel}:generateContent*" => Http::response([], 429)
        ]);
        // 先建立一筆已達配額的使用記錄
        AiUsageLog::create([
            'provider' => 'gemini',
            'model' => $this->geminiModel,
            'usage_date' => today(),
            'request_count' => 1000, // 預設配額是1000
        ]);

        $agentLoop = AgentLoop::create([
            'test_failure_content' => 'Test failed...',
        ]);

        $service = app(AiAgentService::class);
        $this->assertTrue($service->hasExceededQuota());

        $result = $service->analyzeTestFailure($agentLoop);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('quota exceeded', $result['reason']);
    }

    #[Test]
    public function it_correctly_accumulates_usage_metrics()
    {
        $service = app(AiAgentService::class);

        // 連續呼叫10次來驗證數值累加正確
        // 注意：這不是真正的多程序併發測試，僅驗證原子累加邏輯
        // 真正的併發測試需要特殊的資料庫隔離測試環境
        for ($i = 0; $i < 10; $i++) {
            $service->recordUsage(10, 5);
        }

        $usageLog = AiUsageLog::first();
        $this->assertEquals(10, $usageLog->request_count);
        $this->assertEquals(100, $usageLog->prompt_tokens);
        $this->assertEquals(50, $usageLog->completion_tokens);
        $this->assertEquals(150, $usageLog->total_tokens);
    }
}
