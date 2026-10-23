<?php

namespace App\Support;

use Symfony\Component\Process\Process;
use RuntimeException;

class AiAgentTestProcess
{
    public const ENVIRONMENT = [
        'APP_ENV' => 'testing',
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => ':memory:',
    ];

    /** Create a PHPUnit process only after Laravel confirms its effective DB is isolated. */
    public static function create(array $command, ?string $workingDirectory = null): Process
    {
        $environment = self::ENVIRONMENT;
        $bootstrap = <<<'PHP'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo json_encode([
    'driver' => config('database.default'),
    'database' => config('database.connections.'.config('database.default').'.database'),
]);
PHP;
        $preflight = new Process([PHP_BINARY, '-r', $bootstrap], $workingDirectory ?? base_path(), $environment);
        $preflight->setTimeout(30);
        $preflight->run();

        $configuration = json_decode(trim($preflight->getOutput()), true);
        if (!$preflight->isSuccessful()) {
            throw new RuntimeException('Refusing to run PHPUnit: Laravel database configuration preflight failed.');
        }
        self::assertIsolatedConfiguration(is_array($configuration) ? $configuration : []);

        return new Process($command, $workingDirectory ?? base_path(), $environment);
    }

    public static function assertIsolatedConfiguration(array $configuration): void
    {
        if (($configuration['driver'] ?? null) !== 'sqlite'
            || ($configuration['database'] ?? null) !== ':memory:') {
            throw new RuntimeException('Refusing to run PHPUnit: isolated database check failed (expected sqlite :memory:).');
        }
    }
}
