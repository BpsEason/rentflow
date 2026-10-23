<?php

namespace Tests\Feature;

use App\Jobs\ProcessAgentLoop;
use App\Models\AgentLoop;
use App\Support\AiAgentTestProcess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class AiAgentSafetyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function recovery_only_resets_eligible_records_and_dispatches_them_once(): void
    {
        Queue::fake();
        $now = now();
        $eligible = [
            $this->loop('waiting_quota', $now->copy()->subMinute(), $now->copy()->subSeconds(45)),
            $this->loop('processing', $now->copy()->subMinutes(6), null, $now->copy()->subMinutes(6)),
            $this->loop('pending', $now->copy()->subMinutes(2), $now->copy()->subMinute()),
            $this->loop('pending', $now->copy()->subMinutes(2), null),
        ];
        $completed = $this->loop('completed', $now->copy()->subMinutes(2), null);
        $failed = $this->loop('failed', $now->copy()->subMinutes(2), null);
        $recentProcessing = $this->loop('processing', $now->copy()->subMinute(), null, $now->copy()->subMinute());

        $this->artisan('ai:agent recover')->assertSuccessful();

        foreach ($eligible as $loop) {
            $this->assertSame('pending', $loop->fresh()->status);
        }
        $this->assertSame('completed', $completed->fresh()->status);
        $this->assertSame('failed', $failed->fresh()->status);
        $this->assertSame('processing', $recentProcessing->fresh()->status);
        Queue::assertPushed(ProcessAgentLoop::class, 4);
    }

    #[Test]
    public function queued_model_serialization_restores_an_existing_loop(): void
    {
        $loop = $this->loop('pending', now()->subMinute(), null);

        $restored = unserialize(serialize(new ProcessAgentLoop($loop)));

        $this->assertInstanceOf(AgentLoop::class, $restored->agentLoop);
        $this->assertSame($loop->id, $restored->agentLoop->id);
    }

    #[Test]
    public function ai_agent_phpunit_process_receives_isolated_environment(): void
    {
        $process = AiAgentTestProcess::create([
            PHP_BINARY,
            '-r',
            'echo json_encode([getenv("APP_ENV"), getenv("DB_CONNECTION"), getenv("DB_DATABASE")]);',
        ]);
        $process->run();

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $this->assertSame(['testing', 'sqlite', ':memory:'], json_decode($process->getOutput(), true));

        $bootstrapProcess = AiAgentTestProcess::create([
            PHP_BINARY,
            '-r',
            'require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); echo json_encode([config("database.default"), config("database.connections.".config("database.default").".database")]);',
        ]);
        $bootstrapProcess->run();
        $this->assertTrue($bootstrapProcess->isSuccessful(), $bootstrapProcess->getErrorOutput());
        $this->assertSame(['sqlite', ':memory:'], json_decode($bootstrapProcess->getOutput(), true));
    }

    #[Test]
    public function phpunit_is_refused_when_laravel_resolves_a_non_isolated_database(): void
    {
        $this->expectException(RuntimeException::class);
        AiAgentTestProcess::assertIsolatedConfiguration([
            'driver' => 'mysql',
            'database' => 'rentflow',
        ]);
    }

    #[Test]
    public function installed_symfony_process_reports_timeouts_with_an_exception(): void
    {
        $process = new Process([PHP_BINARY, '-r', 'usleep(500000);']);
        $process->setTimeout(0.001);

        try {
            $process->run();
            $this->fail('Expected Symfony Process to throw when its timeout is exceeded.');
        } catch (ProcessTimedOutException $exception) {
            $this->assertSame($process, $exception->getProcess());
        }
    }

    private function loop(string $status, $createdAt, $nextRetryAt, $processingStartedAt = null): AgentLoop
    {
        $loop = AgentLoop::create([
            'test_failure_content' => 'isolated test fixture',
            'status' => $status,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
            'next_retry_at' => $nextRetryAt,
            'processing_started_at' => $processingStartedAt,
        ]);
        AgentLoop::whereKey($loop->id)->update(['created_at' => $createdAt]);

        return $loop->fresh();
    }
}
