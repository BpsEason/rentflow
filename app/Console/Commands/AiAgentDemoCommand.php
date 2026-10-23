<?php

namespace App\Console\Commands;

use App\Models\AgentLoop;
use App\Services\AiAgentService;
use App\Support\AiAgentTestProcess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class AiAgentDemoCommand extends Command
{
    protected $signature = 'ai:demo
        {--real : Explicitly call Gemini for the analysis}
        {--resume= : Continue an existing demo loop after a human applies the suggested patch}
        {--max-loops=2 : Maximum PHPUnit rounds}
        {--case=repair : Case name (repair or quota)}';

    protected $description = 'Run the isolated Autonomous AI Code Repair Agent demonstration';

    public function handle(AiAgentService $service): int
    {
        $this->useIsolatedDemoDatabase();
        $real = (bool) $this->option('real');
        if ($real && blank(config('services.gemini.api_key'))) {
            $this->error('Gemini mode requested, but GEMINI_API_KEY is not configured. No request was sent.');
            return self::FAILURE;
        }

        $loop = $this->option('resume')
            ? AgentLoop::find($this->option('resume'))
            : $this->createLoop();
        if (!$loop) {
            $this->error('Demo loop not found in the isolated SQLite database.');
            return self::FAILURE;
        }
        if ($loop->status === 'completed' || $loop->status === 'failed') {
            $this->error("Loop {$loop->id} is already terminal: {$loop->status}.");
            return self::FAILURE;
        }

        $loop->update(['status' => 'processing']);
        $number = (int) $loop->current_loop + 1;
        $loop->update(['current_loop' => $number]);
        $this->newLine();
        $this->info("Agent Loop #{$loop->id} | iteration {$number}/{$loop->max_loops} | mode: " . ($real ? 'REAL GEMINI' : 'OFFLINE SIMULATION'));

        $test = $this->runIsolatedPhpunit($loop->test_path);
        $loop->update([
            'last_test_exit_code' => $test['exit_code'],
            'last_test_output' => $test['output'],
            'last_test_summary' => json_encode(['exit_code' => $test['exit_code']]),
            'loop_completed_at' => now(),
        ]);
        $this->line('PHPUnit: ' . ($test['exit_code'] === 0 ? '<fg=green>PASS</>' : "<fg=red>FAIL (exit {$test['exit_code']})</>"));

        if ($test['exit_code'] === 0) {
            $loop->update(['status' => 'completed', 'completed_at' => now(), 'termination_reason' => 'Tests passed successfully']);
            $this->recordRound($loop, $number, $test, null, $real ? 'gemini' : 'offline simulation', 0, 0, 'passed');
            $this->info('Final status: completed — Tests passed successfully');
            $this->line('Token usage: ' . $this->tokenTotal($loop->id) . ' (demo database)');
            return self::SUCCESS;
        }

        $this->line($this->failureExcerpt($test['output']));
        $loop->update(['test_failure_content' => $test['output']]);
        $this->line('Failure summary: expected discount total 90; actual total 100.');
        if (!$real && $this->option('case') === 'quota') {
            $analysis = ['success' => false, 'reason' => 'Simulated quota exhaustion (offline scenario; no Gemini request).'];
        } elseif ($real) {
            $analysis = $service->analyzeTestFailure($loop->fresh());
        } else {
            $analysis = [
                'success' => true,
                'analysis' => json_encode([
                    'error_summary' => 'The total remains 100 instead of 90.',
                    'root_cause' => 'The test fixture assigns subtotal directly to total and never applies the 10% discount.',
                    'fixes' => [[
                        'file_path' => base_path('tests/Fixtures/AgentDemo/RepairExampleTest.php'),
                        'search_code' => '$total = $subtotal; // Intentional defect: the discount is not applied.',
                        'replace_code' => '$total = (int) ($subtotal * (1 - $discountRate));',
                    ]],
                    'verification' => 'Re-run tests/Fixtures/AgentDemo/RepairExampleTest.php after human review and apply the proposed one-line change.',
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
            ];
        }

        if (!$analysis['success']) {
            $reason = (string) ($analysis['reason'] ?? 'Unknown analysis error');
            if (filled(config('services.gemini.api_key'))) {
                $reason = str_replace((string) config('services.gemini.api_key'), '[redacted]', $reason);
            }
            $quota = str_contains(strtolower($reason), 'quota') || str_contains($reason, '429');
            $status = $quota ? 'waiting_quota' : 'failed';
            $loop->update(['status' => $status, 'failure_reason' => $reason, 'termination_reason' => $quota ? 'Quota unavailable' : 'AI analysis failed']);
            $this->recordRound($loop, $number, $test, $reason, $real ? 'gemini' : 'offline simulation', 0, 0, $status);
            $this->warn('Analysis unavailable: ' . $reason);
            $this->line("Final status: {$status} | tokens: " . $this->tokenTotal($loop->id));
            return self::FAILURE;
        }

        $loop->update(['ai_analysis' => $analysis['analysis'], 'provider' => $real ? 'gemini' : 'offline-demo', 'model' => $real ? config('services.gemini.model') : 'deterministic-fixture']);
        $this->line(($real ? 'Gemini analysis:' : 'Offline simulated analysis (not Gemini):'));
        $this->line($analysis['analysis']);
        $this->line('Fix status: proposed; not applied. Human review required.');
        $prompt = (int) ($analysis['prompt_tokens'] ?? 0);
        $completion = (int) ($analysis['completion_tokens'] ?? 0);
        $this->recordRound($loop, $number, $test, $analysis['analysis'], $real ? 'gemini' : 'offline simulation', $prompt, $completion, 'awaiting_human_review');

        if ($number >= $loop->max_loops) {
            $loop->update(['status' => 'failed', 'failure_reason' => "Maximum loop iterations ({$loop->max_loops}) reached", 'termination_reason' => 'Max loops exceeded']);
            $this->warn('Final status: failed | Max loops exceeded');
            return self::FAILURE;
        }
        $loop->update(['status' => 'pending', 'termination_reason' => 'Awaiting human review and patch confirmation']);
        $this->line('Next iteration: paused until a human reviews and applies the suggestion.');
        $this->line("Review the fixture, apply the patch manually, then run: php artisan ai:demo --resume={$loop->id}" . ($real ? ' --real' : ''));
        $this->line('Token usage: ' . $this->tokenTotal($loop->id) . ($real ? ' (reported by Gemini)' : ' (offline simulation: 0)'));
        return self::SUCCESS;
    }

    private function useIsolatedDemoDatabase(): void
    {
        $path = database_path('agent-demo.sqlite');
        if (!is_file($path)) {
            touch($path);
        }
        config(['database.connections.agent_demo' => [
            'driver' => 'sqlite', 'database' => $path, 'prefix' => '', 'foreign_key_constraints' => true,
        ], 'database.default' => 'agent_demo']);
        DB::purge('agent_demo');

        foreach ([
            '2026_10_21_035557_create_ai_usage_logs_table.php',
            '2026_10_21_035606_create_agent_loops_table.php',
            '2026_10_21_080657_add_loop_columns_to_agent_loops_table.php',
            '2026_10_23_000000_create_agent_loop_demo_runs_table.php',
        ] as $migration) {
            Artisan::call('migrate', ['--database' => 'agent_demo', '--path' => "database/migrations/{$migration}", '--force' => true]);
            if (Artisan::output() && str_contains(Artisan::output(), 'FAIL')) {
                throw new \RuntimeException(Artisan::output());
            }
        }
    }

    private function createLoop(): AgentLoop
    {
        $path = 'tests/Fixtures/AgentDemo/RepairExampleTest.php';
        $max = max(1, (int) $this->option('max-loops'));
        return AgentLoop::create([
            'test_failure_content' => 'Discount of 10% on 100 should total 90, but actual result is 100.',
            'status' => 'pending', 'current_loop' => 0, 'max_loops' => $max, 'test_path' => $path,
            'auto_apply_fixes' => false, 'allowed_files' => [$path],
        ]);
    }

    private function runIsolatedPhpunit(string $path): array
    {
        $process = AiAgentTestProcess::create([PHP_BINARY, base_path('vendor/bin/phpunit'), '--no-configuration', $path], base_path());
        $process->setTimeout(120);
        $process->run();
        return ['exit_code' => $process->getExitCode() ?? -1, 'output' => $process->getOutput() . $process->getErrorOutput()];
    }

    private function failureExcerpt(string $output): string
    {
        $lines = preg_split('/\R/', trim($output));
        $failureAt = null;
        foreach ($lines as $index => $line) {
            if (str_contains($line, 'There was ') || str_contains($line, 'There were ')) {
                $failureAt = $index;
                break;
            }
        }
        return implode(PHP_EOL, array_slice($lines, $failureAt ?? max(0, count($lines) - 8), 8));
    }

    private function recordRound(AgentLoop $loop, int $number, array $test, ?string $analysis, string $source, int $prompt, int $completion, string $status): void
    {
        DB::table('agent_loop_demo_runs')->updateOrInsert(
            ['agent_loop_id' => $loop->id, 'loop_number' => $number],
            ['test_exit_code' => $test['exit_code'], 'test_output' => $test['output'], 'analysis' => $analysis,
                'analysis_source' => $source, 'prompt_tokens' => $prompt, 'completion_tokens' => $completion,
                'result_status' => $status, 'created_at' => now(), 'updated_at' => now()]
        );
    }

    private function tokenTotal(int $id): int
    {
        return (int) DB::table('agent_loop_demo_runs')->where('agent_loop_id', $id)->sum(DB::raw('prompt_tokens + completion_tokens'));
    }
}
