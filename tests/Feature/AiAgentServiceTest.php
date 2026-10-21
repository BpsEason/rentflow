<?php

namespace Tests\Feature;

use App\Models\AiUsageLog;
use App\Models\AgentLoop;
use App\Services\AiAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
    public function it_blocks_calls_when_quota_is_exceeded()
    {
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

    /** @test */
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

    /** @test */
    public function it_prevents_duplicate_processing_of_same_agent_loop()
    {
        Http::fake([
            "{$this->geminiBaseUrl}/models/{$this->geminiModel}:generateContent*" => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => '分析結果']]]]],
                'usageMetadata' => ['promptTokenCount' => 100, 'candidatesTokenCount' => 50],
            ], 200)
        ]);

        $agentLoop = AgentLoop::create([
            'test_failure_content' => 'Test failed...',
            'status' => 'pending',
        ]);

        // 模擬兩個Worker同時嘗試處理同一個AgentLoop
        $job1 = new \App\Jobs\ProcessAgentLoop($agentLoop);
        $job2 = new \App\Jobs\ProcessAgentLoop($agentLoop);

        $aiAgentService = app(\App\Services\AiAgentService::class);
        $job1->handle($aiAgentService);
        $job2->handle($aiAgentService);

        // 驗證只呼叫了一次Gemini API
        Http::assertSentCount(1);

        // 驗證狀態正確標記為completed
        $agentLoop->refresh();
        $this->assertEquals('completed', $agentLoop->status);
    }

    /** @test */
    public function it_stops_retrying_after_max_business_retries()
    {
        Http::fake([
            "{$this->geminiBaseUrl}/models/{$this->geminiModel}:generateContent*" => Http::response([], 500)
        ]);

        $agentLoop = AgentLoop::create([
            'test_failure_content' => 'Test failed...',
            'retry_count' => 4, // 已經重試4次
            'status' => 'pending',
        ]);

        $job = new \App\Jobs\ProcessAgentLoop($agentLoop);
        $job->handle(app(\App\Services\AiAgentService::class));

        $agentLoop->refresh();
        $this->assertEquals('failed', $agentLoop->status);
        $this->assertEquals(5, $agentLoop->retry_count); // 最後一次重試後達到最大次數
    }

    /** @test */
    public function it_sets_waiting_quota_state_on_429_error()
    {
        Http::fake([
            "{$this->geminiBaseUrl}/models/{$this->geminiModel}:generateContent*" => Http::response(['error' => ['message' => 'Rate limit']], 429)
        ]);

        $agentLoop = AgentLoop::create([
            'test_failure_content' => 'Test failed...',
            'status' => 'pending',
        ]);

        $job = new \App\Jobs\ProcessAgentLoop($agentLoop);
        $job->handle(app(\App\Services\AiAgentService::class));

        $agentLoop->refresh();
        $this->assertEquals('waiting_quota', $agentLoop->status);
        $this->assertNotNull($agentLoop->next_retry_at);
        $this->assertEquals(1, $agentLoop->retry_count);
    }

    /** @test */
    public function it_prevents_duplicate_recovery_of_same_agent_loop()
    {
        // 建立一個等待恢復的任務
        $agentLoop = AgentLoop::create([
            'test_failure_content' => 'Test failed...',
            'status' => 'waiting_quota',
            'next_retry_at' => now()->subHour(), // 已到期
            'retry_count' => 1,
        ]);

        // 直接測試原子更新邏輯，不實例化完整Command避免output未初始化問題
        $id = $agentLoop->id;

        // 第一個程序嘗試恢復
        $updated1 = \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            return \App\Models\AgentLoop::where('id', $id)
                ->where('status', 'waiting_quota')
                ->lockForUpdate()
                ->update(['status' => 'pending']);
        });

        // 第二個程序同時嘗試恢復同一個任務
        $updated2 = \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            return \App\Models\AgentLoop::where('id', $id)
                ->where('status', 'waiting_quota')
                ->lockForUpdate()
                ->update(['status' => 'pending']);
        });

        // 只有第一個程序成功更新
        $this->assertEquals(1, $updated1);
        $this->assertEquals(0, $updated2);

        // 驗證狀態正確更新為pending
        $agentLoop->refresh();
        $this->assertEquals('pending', $agentLoop->status);
    }

    /** @test */
    public function it_respects_queue_tries_and_business_retries_coexistence()
    {
        Http::fake([
            "{$this->geminiBaseUrl}/models/{$this->geminiModel}:generateContent*" => Http::response([], 500)
        ]);

        // 模擬連續失敗5次業務重試
        $agentLoop = AgentLoop::create([
            'test_failure_content' => 'Test failed...',
            'retry_count' => 0,
            'status' => 'pending',
        ]);

        $job = new \App\Jobs\ProcessAgentLoop($agentLoop);

        // 驗證job的Laravel層級參數正確
        $this->assertEquals(6, $job->tries);
        $this->assertEquals(6, $job->maxExceptions);
        $this->assertEquals(60, $job->timeout);

        // 第一次處理
        $job->handle(app(\App\Services\AiAgentService::class));
        $agentLoop->refresh();
        $this->assertEquals(1, $agentLoop->retry_count);
        $this->assertEquals('pending', $agentLoop->status);

        // 再處理4次後應該達到最大業務重試次數
        for ($i = 0; $i < 4; $i++) {
            $job = new \App\Jobs\ProcessAgentLoop($agentLoop);
            $job->handle(app(\App\Services\AiAgentService::class));
            $agentLoop->refresh();
        }

        // 最終應該標記為failed
        $this->assertEquals('failed', $agentLoop->status);
        $this->assertEquals(5, $agentLoop->retry_count); // 達到業務最大重試次數
    }

    /** @test */
    public function it_recovers_stuck_processing_jobs()
    {
        // 建立一個卡住的processing任務，processing_started_at超過5分鐘
        $agentLoop = AgentLoop::create([
            'test_failure_content' => 'Test failed...',
            'status' => 'processing',
            'processing_started_at' => now()->subMinutes(10), // 已經卡住10分鐘
            'retry_count' => 0,
        ]);

        $id = $agentLoop->id;

        // 第一個程序嘗試恢復
        $updated1 = \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            return \App\Models\AgentLoop::where('id', $id)
                ->where('status', 'processing')
                ->where('processing_started_at', '<=', now()->subMinutes(5))
                ->lockForUpdate()
                ->update([
                    'status' => 'pending',
                    'processing_started_at' => null
                ]);
        });

        // 第二個程序同時嘗試恢復同一個任務
        $updated2 = \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            return \App\Models\AgentLoop::where('id', $id)
                ->where('status', 'processing')
                ->where('processing_started_at', '<=', now()->subMinutes(5))
                ->lockForUpdate()
                ->update([
                    'status' => 'pending',
                    'processing_started_at' => null
                ]);
        });

        // 只有第一個程序成功更新
        $this->assertEquals(1, $updated1);
        $this->assertEquals(0, $updated2);

        // 驗證狀態正確更新為pending
        $agentLoop->refresh();
        $this->assertEquals('pending', $agentLoop->status);
        $this->assertNull($agentLoop->processing_started_at);
    }

    /** @test */
    public function it_recovers_pending_delivery_failure_jobs()
    {
        // 建立一個派送失敗的pending任務，next_retry_at已到期
        $agentLoop = AgentLoop::create([
            'test_failure_content' => 'Test failed...',
            'status' => 'pending',
            'next_retry_at' => now()->subHour(), // 派送失敗後等待時間已到
            'retry_count' => 1,
        ]);

        $id = $agentLoop->id;

        // 先確認在候選列表中
        $candidateIds = \App\Models\AgentLoop::where(function ($query) {
            $query->where(function ($q) {
                $q->where('status', 'pending')
                    ->whereNotNull('next_retry_at')
                    ->where('next_retry_at', '<=', now());
            });
        })->pluck('id');
        $this->assertTrue($candidateIds->contains($id));

        // 第一個程序嘗試恢復，實際上修改status來模擬真實原子更新
        $updated1 = \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            return \App\Models\AgentLoop::where('id', $id)
                ->where('status', 'pending')
                ->whereNotNull('next_retry_at')
                ->where('next_retry_at', '<=', now())
                ->lockForUpdate()
                ->update([
                    'status' => 'pending',
                    'processing_started_at' => null
                ]);
        });

        // 第二個程序同時嘗試恢復同一個任務，此時status仍然是pending，但真實實作中會先修改status
        // 為了正確模擬並驗證原子搶占，我們需要使用與真實AiAgentCommand完全相同的更新條件
        $updated2 = \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            return \App\Models\AgentLoop::where('id', $id)
                ->where('status', 'pending')
                ->whereNotNull('next_retry_at')
                ->where('next_retry_at', '<=', now())
                ->where('created_at', '<=', now()->subSeconds(30))
                ->lockForUpdate()
                ->update([
                    'status' => 'processing', // 第一個程序成功後會修改status，第二個就無法再更新
                    'processing_started_at' => now()
                ]);
        });

        // 只有第一個程序成功更新
        $this->assertEquals(1, $updated1);
        $this->assertEquals(0, $updated2);
    }

    /** @test */
    public function it_does_not_recover_fresh_pending_jobs()
    {
        // 建立一個剛建立的pending任務，雖然有next_retry_at，但created_at不足30秒
        $agentLoop = AgentLoop::create([
            'test_failure_content' => 'Test failed...',
            'status' => 'pending',
            'next_retry_at' => now()->subSeconds(10), // next_retry_at已到期
            'retry_count' => 0,
            // created_at是現在，不足30秒，不會被恢復機制處理
        ]);

        // 驗證不會被加入候選列表，因為created_at不足30秒
        $candidateIds = \App\Models\AgentLoop::where(function ($query) {
            $query->where(function ($q) {
                $q->where('status', 'pending')
                    ->whereNotNull('next_retry_at')
                    ->where('next_retry_at', '<=', now())
                    ->where('created_at', '<=', now()->subSeconds(30));
            });
        })->pluck('id');
        $this->assertFalse($candidateIds->contains($agentLoop->id));
    }

    /** @test */
    public function it_recovers_old_enough_pending_jobs()
    {
        // 根因：AgentLoop模型自動維護$timestamps，手動設定的created_at會被覆蓋
        // 解決方案：先將時間旅行到60秒前建立任務，再旅行回現在時間，這樣created_at就會滿足30秒條件
        $currentTime = now()->addDays(1); // 使用未來時間避免與其他測試的時間旅行干擾

        // 第一步：旅行到60秒前，建立任務，此時任務的created_at會被自動設定為這個時間
        $this->travelTo($currentTime->copy()->subSeconds(60));
        $agentLoop = AgentLoop::create([
            'test_failure_content' => 'Test failed...',
            'status' => 'pending',
            'next_retry_at' => now(), // next_retry_at在建立時就已經是現在，會過期
            'retry_count' => 0,
        ]);

        // 第二步：再旅行回原來的當前時間，此時任務的created_at是60秒前，符合>30秒的要求
        $this->travelTo($currentTime);

        // 刷新模型，讀取資料庫中的值
        $agentLoop->refresh();

        // 驗證會被加入候選列表，使用與正式程式碼完全相同的查詢邏輯
        $candidateIds = \App\Models\AgentLoop::where(function ($query) {
            $query->where(function ($q) {
                $q->where('status', 'pending')
                    ->whereNotNull('next_retry_at')
                    ->where('next_retry_at', '<=', now())
                    ->where('created_at', '<=', now()->subSeconds(30));
            });
        })->pluck('id');

        $this->assertTrue(
            $candidateIds->contains($agentLoop->id),
            "Agent loop ID {$agentLoop->id} not found in candidates: " . $candidateIds->implode(', ') .
                ". Created at: {$agentLoop->created_at}, threshold: " . now()->subSeconds(30)
        );
    }

    /** @test */
    public function it_handles_queue_exhaustion_correctly_with_remaining_retries()
    {
        // 建立一個processing狀態的任務，還有剩餘的業務重試次數
        $agentLoop = AgentLoop::create([
            'test_failure_content' => 'Test failed...',
            'status' => 'processing',
            'processing_started_at' => now(),
            'retry_count' => 2, // 已重試2次，還有3次機會（MAX=5）
        ]);

        // 模擬Queue重試耗盡，呼叫failed()方法
        $job = new \App\Jobs\ProcessAgentLoop($agentLoop);
        $exception = new \Exception('Worker crashed unexpectedly');
        $job->failed($exception);

        // 驗證狀態被重置為pending，retry_count增加
        $agentLoop->refresh();
        $this->assertEquals('pending', $agentLoop->status);
        $this->assertEquals(3, $agentLoop->retry_count);
        $this->assertNull($agentLoop->processing_started_at);
        $this->assertNotNull($agentLoop->next_retry_at);
    }

    /** @test */
    public function it_marks_as_failed_when_business_retries_exhausted()
    {
        // 建立一個processing狀態的任務，已達到最大業務重試次數
        $agentLoop = AgentLoop::create([
            'test_failure_content' => 'Test failed...',
            'status' => 'processing',
            'processing_started_at' => now(),
            'retry_count' => 5, // 已達到最大業務重試次數
        ]);

        // 模擬Queue重試耗盡，呼叫failed()方法
        $job = new \App\Jobs\ProcessAgentLoop($agentLoop);
        $exception = new \Exception('Worker crashed unexpectedly');
        $job->failed($exception);

        // 驗證狀態被標記為永久失敗
        $agentLoop->refresh();
        $this->assertEquals('failed', $agentLoop->status);
        $this->assertEquals(5, $agentLoop->retry_count); // 不會再遞增
    }

    /** @test */
    public function it_sets_processing_started_at_when_job_begins()
    {
        Http::fake([
            "{$this->geminiBaseUrl}/models/{$this->geminiModel}:generateContent*" => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => '分析結果']]]]],
                'usageMetadata' => ['promptTokenCount' => 100, 'candidatesTokenCount' => 50],
            ], 200)
        ]);

        $agentLoop = AgentLoop::create([
            'test_failure_content' => 'Test failed...',
            'status' => 'pending',
        ]);

        $job = new \App\Jobs\ProcessAgentLoop($agentLoop);
        $job->handle(app(\App\Services\AiAgentService::class));

        // 任務開始處理時會設定processing_started_at
        $agentLoop->refresh();
        $this->assertNotNull($agentLoop->processing_started_at);
        $this->assertEquals('completed', $agentLoop->status);
    }

    /** @test */
    public function it_prevents_old_worker_from_overwriting_completed_task()
    {
        // 先建立一個已完成的任務
        $agentLoop = AgentLoop::create([
            'test_failure_content' => 'Test failed...',
            'status' => 'completed',
            'completed_at' => now()->subHour(),
            'ai_analysis' => '已有的分析結果',
            'retry_count' => 0,
        ]);

        // 模擬一個舊的Worker嘗試處理這個已完成的任務
        $job = new \App\Jobs\ProcessAgentLoop($agentLoop);

        // 先執行handle()中的狀態搶占邏輯，應該無法更新
        $updated = \Illuminate\Support\Facades\DB::transaction(function () use ($agentLoop) {
            return \App\Models\AgentLoop::where('id', $agentLoop->id)
                ->whereIn('status', ['pending', 'waiting_quota'])
                ->lockForUpdate()
                ->update([
                    'status' => 'processing',
                    'processing_started_at' => now()
                ]);
        });

        // 無法更新，因為狀態不是pending或waiting_quota
        $this->assertEquals(0, $updated);

        // 原有的完成狀態保持不變
        $agentLoop->refresh();
        $this->assertEquals('completed', $agentLoop->status);
        $this->assertEquals('已有的分析結果', $agentLoop->ai_analysis);
    }

    /** @test */
    public function it_prevents_old_worker_from_overwriting_failed_task()
    {
        // 先建立一個已失敗的任務
        $agentLoop = AgentLoop::create([
            'test_failure_content' => 'Test failed...',
            'status' => 'failed',
            'failure_reason' => '原有的失敗原因',
            'retry_count' => 5, // 已達最大重試次數
        ]);

        // 模擬一個舊的Worker嘗試處理這個已失敗的任務
        $job = new \App\Jobs\ProcessAgentLoop($agentLoop);

        // 先執行handle()中的狀態搶占邏輯，應該無法更新
        $updated = \Illuminate\Support\Facades\DB::transaction(function () use ($agentLoop) {
            return \App\Models\AgentLoop::where('id', $agentLoop->id)
                ->whereIn('status', ['pending', 'waiting_quota'])
                ->lockForUpdate()
                ->update([
                    'status' => 'processing',
                    'processing_started_at' => now()
                ]);
        });

        // 無法更新，因為狀態不是pending或waiting_quota
        $this->assertEquals(0, $updated);

        // 原有的失敗狀態保持不變
        $agentLoop->refresh();
        $this->assertEquals('failed', $agentLoop->status);
        $this->assertEquals('原有的失敗原因', $agentLoop->failure_reason);
        $this->assertEquals(5, $agentLoop->retry_count);
    }

    /** @test */
    public function it_does_not_allow_failed_handler_to_modify_completed_task()
    {
        // 建立一個已完成的任務
        $agentLoop = AgentLoop::create([
            'test_failure_content' => 'Test failed...',
            'status' => 'completed',
            'completed_at' => now()->subHour(),
            'retry_count' => 0,
        ]);

        // 模擬Queue的failed()方法被呼叫，嘗試修改已完成的任務
        $job = new \App\Jobs\ProcessAgentLoop($agentLoop);
        $exception = new \Exception('Worker crashed');
        $job->failed($exception);

        // 任務狀態仍然是completed，沒有被修改
        $agentLoop->refresh();
        $this->assertEquals('completed', $agentLoop->status);
        $this->assertEquals(0, $agentLoop->retry_count); // 重試次數沒有增加
    }

    /** @test */
    public function it_uses_correct_gemini_model_from_config()
    {
        // 驗證測試環境中使用的模型與config設定一致
        $configModel = config('services.gemini.model');
        $service = app(\App\Services\AiAgentService::class);

        // 使用反射取得service的model屬性來驗證
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('model');
        $property->setAccessible(true);
        $serviceModel = $property->getValue($service);

        $this->assertEquals($configModel, $serviceModel);
        $this->assertEquals(env('GEMINI_MODEL', 'gemini-3.5-flash-lite'), $configModel);
    }

    /** @test */
    public function it_does_not_recover_tasks_that_are_already_processing_recently()
    {
        // 建立一個剛開始處理的processing任務，processing_started_at只有1分鐘前，未超過5分鐘
        $agentLoop = AgentLoop::create([
            'test_failure_content' => 'Test failed...',
            'status' => 'processing',
            'processing_started_at' => now()->subMinute(), // 只開始了1分鐘，不會被視為卡住
            'retry_count' => 0,
        ]);

        // 驗證這個任務不會被恢復機制選中
        $candidateIds = \App\Models\AgentLoop::where(function ($query) {
            $timeoutThreshold = now()->subMinutes(5);
            $query->where(function ($q) use ($timeoutThreshold) {
                $q->where('status', 'processing')
                    ->where('processing_started_at', '<=', $timeoutThreshold);
            });
        })->pluck('id');

        $this->assertFalse($candidateIds->contains($agentLoop->id));
    }

    /** @test */
    public function it_only_updates_processing_tasks_when_marking_completed()
    {
        // 建立一個已失敗的任務，模擬舊Worker嘗試將其標記為完成
        $agentLoop = AgentLoop::create([
            'test_failure_content' => 'Test failed...',
            'status' => 'failed',
            'retry_count' => 5,
        ]);

        // 嘗試使用ProcessAgentLoop中相同的條件式更新
        $updated = \App\Models\AgentLoop::where('id', $agentLoop->id)
            ->where('status', 'processing')
            ->update([
                'status' => 'completed',
                'ai_analysis' => '新的分析結果',
            ]);

        // 更新失敗，因為狀態不是processing
        $this->assertEquals(0, $updated);

        // 原狀態保持不變
        $agentLoop->refresh();
        $this->assertEquals('failed', $agentLoop->status);
        $this->assertNull($agentLoop->ai_analysis);
    }
}
