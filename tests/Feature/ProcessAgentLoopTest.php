<?php

namespace Tests\Feature;

use App\Jobs\ProcessAgentLoop;
use App\Models\AgentLoop;
use App\Services\AiAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProcessAgentLoopTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_max_loops_1_executes_once_only()
    {
        Queue::fake();

        // 建立有 test_path 的 agent loop，max_loops=1，初始 current_loop=1
        $agentLoop = AgentLoop::create([
            'test_path' => 'tests/Unit/ExampleTest.php',
            'test_failure_content' => 'Initial test failure',
            'max_loops' => 1,
            'current_loop' => 1,
            'status' => 'pending',
            'auto_apply_fixes' => true,
        ]);

        // Mock AiAgentService
        $mockService = Mockery::mock(AiAgentService::class);

        // 模擬測試失敗（需要進入AI分析）
        $mockService->shouldReceive('runTests')->once()->andReturn([
            'success' => false,
            'exit_code' => 1,
            'output' => 'Test failed',
            'error_output' => '',
            'timed_out' => false,
            'summary' => [],
        ]);

        // 模擬AI分析成功，返回有fixes的JSON
        $mockService->shouldReceive('analyzeTestFailure')->once()->andReturn([
            'success' => true,
            'analysis' => json_encode([
                'error_summary' => 'Test error',
                'root_cause' => 'Missing assertion',
                'fixes' => [
                    ['file_path' => 'tests/Unit/ExampleTest.php', 'patch' => '...']
                ],
            ]),
        ]);

        $mockService->shouldReceive('validateFixes')->once()->andReturn([
            'valid' => true,
            'valid_fixes' => [],
            'errors' => [],
        ]);

        $mockService->shouldReceive('applyFixes')->once()->andReturn([
            'success' => true,
            'patch_history' => [],
            'modified_files' => [],
        ]);

        // 執行Job
        $job = new ProcessAgentLoop($agentLoop);
        $job->handle($mockService);

        // 刷新模型
        $agentLoop->refresh();

        // 驗證狀態變為failed（因已達max_loops）
        $this->assertEquals('failed', $agentLoop->status);
        $this->assertEquals('Max loops exceeded', $agentLoop->termination_reason);
        $this->assertEquals(1, $agentLoop->current_loop);

        // 驗證沒有派送新的Job
        Queue::assertNothingPushed();
    }

    #[Test]
    public function test_max_loops_2_executes_two_loops()
    {
        Queue::fake();

        // 建立有 test_path 的 agent loop，max_loops=2，初始 current_loop=1
        $agentLoop = AgentLoop::create([
            'test_path' => 'tests/Unit/ExampleTest.php',
            'test_failure_content' => 'Initial test failure',
            'max_loops' => 2,
            'current_loop' => 1,
            'status' => 'pending',
            'auto_apply_fixes' => true,
        ]);

        // Mock AiAgentService
        $mockService = Mockery::mock(AiAgentService::class);

        $mockService->shouldReceive('runTests')->once()->andReturn([
            'success' => false,
            'exit_code' => 1,
            'output' => 'Test failed',
            'error_output' => '',
            'timed_out' => false,
            'summary' => [],
        ]);

        $mockService->shouldReceive('analyzeTestFailure')->once()->andReturn([
            'success' => true,
            'analysis' => json_encode([
                'error_summary' => 'Test error',
                'root_cause' => 'Missing assertion',
                'fixes' => [
                    ['file_path' => 'tests/Unit/ExampleTest.php', 'patch' => '...']
                ],
            ]),
        ]);

        $mockService->shouldReceive('validateFixes')->once()->andReturn([
            'valid' => true,
            'valid_fixes' => [],
            'errors' => [],
        ]);

        $mockService->shouldReceive('applyFixes')->once()->andReturn([
            'success' => true,
            'patch_history' => [],
            'modified_files' => [],
        ]);

        // 執行Job
        $job = new ProcessAgentLoop($agentLoop);
        $job->handle($mockService);

        // 刷新模型
        $agentLoop->refresh();

        // 驗證current_loop更新為2，狀態變回pending
        $this->assertEquals('pending', $agentLoop->status);
        $this->assertEquals(2, $agentLoop->current_loop);

        // 驗證有派送新的Job
        Queue::assertPushed(ProcessAgentLoop::class, function ($job) use ($agentLoop) {
            return $job->agentLoop->id === $agentLoop->id;
        });
    }

    #[Test]
    public function test_valid_analysis_with_empty_fixes_terminates()
    {
        Queue::fake();

        // 建立有test_path但AI返回空fixes的情境
        $agentLoop = AgentLoop::create([
            'test_path' => 'tests/Unit/ExampleTest.php',
            'test_failure_content' => 'Initial test failure',
            'max_loops' => 5,
            'current_loop' => 1,
            'status' => 'pending',
            'auto_apply_fixes' => true,
        ]);

        $mockService = Mockery::mock(AiAgentService::class);

        $mockService->shouldReceive('runTests')->once()->andReturn([
            'success' => false,
            'exit_code' => 1,
            'output' => 'Test failed',
            'error_output' => '',
            'timed_out' => false,
            'summary' => [],
        ]);

        // AI分析成功但fixes為空陣列
        $mockService->shouldReceive('analyzeTestFailure')->once()->andReturn([
            'success' => true,
            'analysis' => json_encode([
                'error_summary' => 'Test error',
                'root_cause' => 'Unable to fix',
                'fixes' => [],
            ]),
        ]);

        // 執行Job
        $job = new ProcessAgentLoop($agentLoop);
        $job->handle($mockService);

        $agentLoop->refresh();

        // 驗證任務標記為failed（有test_path但無fixes）
        $this->assertEquals('failed', $agentLoop->status);
        $this->assertEquals('No fixes proposed', $agentLoop->termination_reason);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function test_invalid_json_triggers_completed_state()
    {
        Queue::fake();

        $agentLoop = AgentLoop::create([
            'test_path' => 'tests/Unit/ExampleTest.php',
            'test_failure_content' => 'Initial test failure',
            'max_loops' => 5,
            'current_loop' => 1,
            'status' => 'pending',
        ]);

        $mockService = Mockery::mock(AiAgentService::class);

        $mockService->shouldReceive('runTests')->once()->andReturn([
            'success' => false,
            'exit_code' => 1,
            'output' => 'Test failed',
            'error_output' => '',
            'timed_out' => false,
            'summary' => [],
        ]);

        // AI返回無效JSON
        $mockService->shouldReceive('analyzeTestFailure')->once()->andReturn([
            'success' => true,
            'analysis' => 'This is not valid JSON',
        ]);

        $job = new ProcessAgentLoop($agentLoop);
        $job->handle($mockService);

        $agentLoop->refresh();

        // 驗證狀態為completed，儲存了原始分析
        $this->assertEquals('completed', $agentLoop->status);
        $this->assertEquals('Analysis completed without automated fixes', $agentLoop->termination_reason);
        $this->assertEquals('This is not valid JSON', $agentLoop->ai_analysis);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function test_missing_required_fields_marks_failed()
    {
        Queue::fake();

        $agentLoop = AgentLoop::create([
            'test_path' => 'tests/Unit/ExampleTest.php',
            'test_failure_content' => 'Initial test failure',
            'max_loops' => 5,
            'current_loop' => 1,
            'status' => 'pending',
        ]);

        $mockService = Mockery::mock(AiAgentService::class);

        $mockService->shouldReceive('runTests')->once()->andReturn([
            'success' => false,
            'exit_code' => 1,
            'output' => 'Test failed',
            'error_output' => '',
            'timed_out' => false,
            'summary' => [],
        ]);

        // JSON有效但缺少必要欄位
        $mockService->shouldReceive('analyzeTestFailure')->once()->andReturn([
            'success' => true,
            'analysis' => json_encode([
                'fixes' => [['file' => 'test.php']],
                // 缺少 error_summary 和 root_cause
            ]),
        ]);

        $job = new ProcessAgentLoop($agentLoop);
        $job->handle($mockService);

        $agentLoop->refresh();

        // 驗證標記為failed
        $this->assertEquals('failed', $agentLoop->status);
        $this->assertEquals('Invalid AI response structure', $agentLoop->termination_reason);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function test_test_path_null_with_test_failure_content_analyzes_once()
    {
        Queue::fake();

        // test_path = NULL，但有test_failure_content
        $agentLoop = AgentLoop::create([
            'test_path' => null,
            'test_failure_content' => 'External test failure content',
            'max_loops' => 5,
            'current_loop' => 1,
            'status' => 'pending',
        ]);

        $mockService = Mockery::mock(AiAgentService::class);

        // 絕對不能呼叫runTests，因為沒有test_path
        $mockService->shouldNotReceive('runTests');

        // AI分析成功，有fixes
        $mockService->shouldReceive('analyzeTestFailure')->once()->andReturn([
            'success' => true,
            'analysis' => json_encode([
                'error_summary' => 'External error',
                'root_cause' => 'External issue',
                'fixes' => [['file_path' => 'file.php', 'patch' => '...']],
            ]),
        ]);

        $mockService->shouldReceive('validateFixes')->once()->andReturn([
            'valid' => true,
            'valid_fixes' => [],
            'errors' => [],
        ]);

        $job = new ProcessAgentLoop($agentLoop);
        $job->handle($mockService);

        $agentLoop->refresh();

        // 驗證任務完成，沒有派送下一輪
        $this->assertEquals('completed', $agentLoop->status);
        $this->assertEquals('Analysis completed with proposed fixes (unverified, no test_path)', $agentLoop->termination_reason);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function test_test_path_null_without_test_failure_content_marks_failed()
    {
        Queue::fake();

        // 既無test_path也無test_failure_content（使用空字串來符合NOT NULL約束）
        $agentLoop = AgentLoop::create([
            'test_path' => null,
            'test_failure_content' => '',
            'max_loops' => 5,
            'current_loop' => 1,
            'status' => 'pending',
        ]);

        $mockService = Mockery::mock(AiAgentService::class);
        $mockService->shouldNotReceive('runTests');
        $mockService->shouldNotReceive('analyzeTestFailure');

        $job = new ProcessAgentLoop($agentLoop);
        $job->handle($mockService);

        $agentLoop->refresh();

        $this->assertEquals('failed', $agentLoop->status);
        $this->assertEquals('Missing test input', $agentLoop->termination_reason);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function test_test_path_exists_tests_pass_completes_successfully()
    {
        Queue::fake();

        $agentLoop = AgentLoop::create([
            'test_path' => 'tests/Unit/ExampleTest.php',
            'test_failure_content' => 'Initial test failure',
            'max_loops' => 5,
            'current_loop' => 1,
            'status' => 'pending',
        ]);

        $mockService = Mockery::mock(AiAgentService::class);

        // 測試直接通過
        $mockService->shouldReceive('runTests')->once()->andReturn([
            'success' => true,
            'exit_code' => 0,
            'output' => 'All tests passed',
            'error_output' => '',
            'timed_out' => false,
            'summary' => ['passed' => 10],
        ]);

        // 不會呼叫AI分析
        $mockService->shouldNotReceive('analyzeTestFailure');

        $job = new ProcessAgentLoop($agentLoop);
        $job->handle($mockService);

        $agentLoop->refresh();

        $this->assertEquals('completed', $agentLoop->status);
        $this->assertEquals('Tests passed successfully', $agentLoop->termination_reason);
        $this->assertEquals(0, $agentLoop->last_test_exit_code);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function test_api_quota_error_enters_waiting_quota_state()
    {
        Queue::fake();

        $agentLoop = AgentLoop::create([
            'test_path' => 'tests/Unit/ExampleTest.php',
            'test_failure_content' => 'Initial test failure',
            'max_loops' => 5,
            'current_loop' => 1,
            'status' => 'pending',
            'retry_count' => 0,
        ]);

        $mockService = Mockery::mock(AiAgentService::class);

        $mockService->shouldReceive('runTests')->once()->andReturn([
            'success' => false,
            'exit_code' => 1,
            'output' => 'Test failed',
            'error_output' => '',
            'timed_out' => false,
            'summary' => [],
        ]);

        // AI分析因配額不足失敗，需要重試
        $mockService->shouldReceive('analyzeTestFailure')->once()->andReturn([
            'success' => false,
            'should_retry' => true,
            'reason' => 'API quota exceeded',
            'retry_delay' => 3600,
        ]);

        $job = new ProcessAgentLoop($agentLoop);
        $job->handle($mockService);

        $agentLoop->refresh();

        // 驗證進入waiting_quota狀態
        $this->assertEquals('waiting_quota', $agentLoop->status);
        $this->assertEquals(1, $agentLoop->retry_count);
        $this->assertNotNull($agentLoop->next_retry_at);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function test_auto_apply_fixes_false_does_not_modify_files()
    {
        Queue::fake();

        $agentLoop = AgentLoop::create([
            'test_path' => 'tests/Unit/ExampleTest.php',
            'test_failure_content' => 'Initial test failure',
            'max_loops' => 2,
            'current_loop' => 1,
            'status' => 'pending',
            'auto_apply_fixes' => false, // 不自動套用修復
        ]);

        $mockService = Mockery::mock(AiAgentService::class);

        $mockService->shouldReceive('runTests')->once()->andReturn([
            'success' => false,
            'exit_code' => 1,
            'output' => 'Test failed',
            'error_output' => '',
            'timed_out' => false,
            'summary' => [],
        ]);

        $mockService->shouldReceive('analyzeTestFailure')->once()->andReturn([
            'success' => true,
            'analysis' => json_encode([
                'error_summary' => 'Test error',
                'root_cause' => 'Missing assertion',
                'fixes' => [
                    ['file_path' => 'tests/Unit/ExampleTest.php', 'patch' => '...']
                ],
            ]),
        ]);

        $mockService->shouldReceive('validateFixes')->once()->andReturn([
            'valid' => true,
            'valid_fixes' => [['file_path' => 'tests/Unit/ExampleTest.php', 'patch' => '...']],
            'errors' => [],
        ]);

        // 絕對不能呼叫applyFixes，因為auto_apply_fixes=false
        $mockService->shouldNotReceive('applyFixes');

        $job = new ProcessAgentLoop($agentLoop);
        $job->handle($mockService);

        $agentLoop->refresh();

        // 仍然會派送下一輪（因為有test_path且未達max_loops）
        $this->assertEquals('pending', $agentLoop->status);
        $this->assertEquals(2, $agentLoop->current_loop);
        Queue::assertPushed(ProcessAgentLoop::class);
    }
}
