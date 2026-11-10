<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use yii\caching\{ArrayCache, FileCache};
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\support\OwnedWorkDirectoryTrait;

use function array_filter;
use function array_values;
use function count;
use function dirname;
use function explode;
use function is_array;
use function is_string;
use function json_decode;
use function str_replace;
use function strpos;
use function substr;
use function trim;
use function var_export;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;

/**
 * Integration tests for when PHPStan executes the {@see ServiceMap} configuration file relative to
 * `parameters.bootstrapFiles`.
 *
 * Each case runs PHPStan in a separate process against files generated in its own temporary directory. A bootstrap file
 * defines a probe constant; the analysis configuration selects {@see ArrayCache} when the constant exists and
 * {@see FileCache} otherwise. Both files append their process ID to an event log, so the order is observed per process
 * and a late bootstrap is distinguished from a bootstrap that never ran.
 */
final class ConfigurationBootstrapOrderTest extends TestCase
{
    use OwnedWorkDirectoryTrait;

    /**
     * Name of the event log written by the generated bootstrap and configuration files.
     */
    private const string EVENT_LOG = 'events.log';

    /**
     * Constant defined only by the generated bootstrap file.
     */
    private const string PROBE_CONSTANT = 'YII2_PHPSTAN_BOOTSTRAP_ORDER_PROBE';

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-bootstrap-order-';

    public function testConfigurationRunsBeforeBootstrapFilesOnColdAndWarmRuns(): void
    {
        $cold = $this->analyse('config.php');

        $mainProcess = self::mainProcess($cold['events']);

        self::assertSame(
            1,
            $cold['exitCode'],
            "Analysis must report errors without crashing.\n" . $cold['stderr'],
        );
        self::assertSame(
            [FileCache::class],
            $cold['types'],
            'Cold run must keep the pre-bootstrap type.',
        );
        self::assertConfigurationPrecedesBootstrap(
            $cold['events'],
        );
        self::assertNotEmpty(
            array_filter(
                $cold['events'],
                static fn(array $event): bool => $event['type'] === 'bootstrap' && $event['pid'] !== $mainProcess,
            ),
            'A worker process must run the bootstrap files.',
        );

        $warm = $this->analyse('config.php');

        self::assertSame(
            1,
            $warm['exitCode'],
            "Analysis must report errors without crashing.\n" . $warm['stderr'],
        );
        self::assertStringContainsString(
            'Result cache restored',
            $warm['stderr'],
            'Warm run must reuse the result cache written by the cold run.',
        );
        self::assertSame(
            [FileCache::class],
            $warm['types'],
            'Warm run must keep the pre-bootstrap type.',
        );
        self::assertConfigurationPrecedesBootstrap(
            $warm['events'],
        );
    }

    public function testConfigurationRunsBeforeBootstrapFilesWithoutWorkers(): void
    {
        $result = $this->analyse('config.php', '--debug');

        $mainProcess = self::mainProcess($result['events']);

        self::assertSame(
            1,
            $result['exitCode'],
            "Analysis must report errors without crashing.\n" . $result['stderr'],
        );
        self::assertSame(
            [FileCache::class],
            $result['types'],
            'Type must be chosen before the bootstrap.',
        );
        self::assertConfigurationPrecedesBootstrap(
            $result['events'],
        );
        self::assertSame(
            [],
            array_values(
                array_filter($result['events'], static fn(array $event): bool => $event['pid'] !== $mainProcess),
            ),
            'Every event must come from the single analysis process.',
        );
    }

    public function testConfigurationWrapperLoadsBootstrapPrerequisitesOnce(): void
    {
        $result = $this->analyse('config-wrapper.php');

        self::assertSame(
            1,
            $result['exitCode'],
            "Analysis must report errors without crashing.\n" . $result['stderr'],
        );
        self::assertSame(
            [ArrayCache::class],
            $result['types'],
            'Configuration must see the constant.',
        );

        $bootstrapsByProcess = [];

        foreach ($result['events'] as $event) {
            if ($event['type'] === 'bootstrap') {
                $bootstrapsByProcess[$event['pid']] = ($bootstrapsByProcess[$event['pid']] ?? 0) + 1;
            } else {
                self::assertTrue(
                    $event['defined'],
                    'Every configuration execution must see the constant.',
                );
            }
        }

        self::assertNotSame(
            [],
            $bootstrapsByProcess,
            'Bootstrap must run.',
        );

        foreach ($bootstrapsByProcess as $count) {
            self::assertSame(
                1,
                $count,
                'Bootstrap must run once per process.',
            );
        }
    }

    protected function setUp(): void
    {
        $this->createOwnedWorkDirectory('src');

        $constant = var_export(self::PROBE_CONSTANT, true);
        $eventLog = var_export(self::EVENT_LOG, true);

        $this->writeFile(
            'bootstrap.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            define({$constant}, true);
            file_put_contents(__DIR__ . '/' . {$eventLog}, 'bootstrap ' . getmypid() . "\\n", FILE_APPEND | LOCK_EX);

            PHP,
        );
        $this->writeFile(
            'config.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            \$defined = defined({$constant});
            file_put_contents(
                __DIR__ . '/' . {$eventLog},
                'config ' . getmypid() . ' ' . (\$defined ? '1' : '0') . "\\n",
                FILE_APPEND | LOCK_EX,
            );

            return [
                'components' => [
                    'cache' => ['class' => \$defined ? 'yii\\caching\\ArrayCache' : 'yii\\caching\\FileCache'],
                ],
            ];

            PHP,
        );
        $this->writeFile(
            'config-wrapper.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            require_once __DIR__ . '/bootstrap.php';

            return require __DIR__ . '/config.php';

            PHP,
        );
        $this->writeFile(
            'src' . DIRECTORY_SEPARATOR . 'Probe.php',
            <<<'PROBE'
            <?php

            declare(strict_types=1);

            function probe(\yii\web\Application $app): void
            {
                \PHPStan\dumpType($app->get('cache'));
            }

            PROBE,
        );
    }

    /**
     * Runs PHPStan in a separate process and collects its exit code, dumped types, error output and event log.
     *
     * @phpstan-return array{
     *     exitCode: int,
     *     events: list<array{type: string, pid: int, defined: bool}>,
     *     stderr: string,
     *     types: list<string>,
     * }
     */
    private function analyse(string $configFile, string ...$options): array
    {
        $this->writeFile(
            'phpstan.neon',
            <<<NEON
            includes:
                - {$this->neonPath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'extension.neon')}

            parameters:
                level: 0
                paths:
                    - {$this->neonPath($this->path('src'))}
                tmpDir: {$this->neonPath($this->path('tmp'))}
                bootstrapFiles:
                    - {$this->neonPath($this->path('bootstrap.php'))}
                yii2:
                    config_path: {$this->neonPath($this->path($configFile))}

            NEON,
        );
        $this->writeFile(
            self::EVENT_LOG,
            '',
        );

        $run = $this->runProcess(
            self::phpstanCommand($this->path('phpstan.neon'), '-vvv', ...$options),
            $this->workDirectory,
            'stdout.txt',
            'stderr.txt',
        );

        return [
            'exitCode' => $run['exitCode'],
            'events' => $this->readEvents(),
            'stderr' => $run['stderr'],
            'types' => self::dumpedTypes($run['stdout']),
        ];
    }

    /**
     * Asserts that, in every process, the first configuration execution precedes the first bootstrap execution and
     * misses the constant, and that the main process runs the bootstrap afterwards.
     *
     * @phpstan-param list<array{type: string, pid: int, defined: bool}> $events
     */
    private static function assertConfigurationPrecedesBootstrap(array $events): void
    {
        $first = $events[0] ?? null;

        self::assertNotNull(
            $first,
            'Event log must not be empty.',
        );
        self::assertSame(
            'config',
            $first['type'],
            'Configuration must run first.',
        );
        self::assertFalse(
            $first['defined'],
            'Configuration must run before the constant exists.',
        );

        $firstByProcess = [];

        foreach ($events as $position => $event) {
            $firstByProcess[$event['pid']][$event['type']] ??= [
                'position' => $position,
                'defined' => $event['defined'],
            ];
        }

        foreach ($firstByProcess as $firstOfProcess) {
            if (isset($firstOfProcess['config'], $firstOfProcess['bootstrap'])) {
                self::assertLessThan(
                    $firstOfProcess['bootstrap']['position'],
                    $firstOfProcess['config']['position'],
                    'Configuration must run before the bootstrap in the same process.',
                );
                self::assertFalse(
                    $firstOfProcess['config']['defined'],
                    'First configuration must miss the constant.',
                );
            }
        }

        self::assertArrayHasKey(
            'bootstrap',
            $firstByProcess[$first['pid']] ?? [],
            'Main process must run the bootstrap after the configuration.',
        );
    }

    /**
     * Extracts the types reported by `PHPStan\dumpType()` from the JSON error output.
     *
     * @return string[] Dumped types in report order.
     *
     * @phpstan-return list<string>
     */
    private static function dumpedTypes(string $stdout): array
    {
        $start = strpos($stdout, '{"totals"');

        if ($start === false) {
            return [];
        }

        $report = json_decode(substr($stdout, $start), true, 512, JSON_THROW_ON_ERROR);
        $files = is_array($report) && is_array($report['files'] ?? null) ? $report['files'] : [];

        $types = [];

        foreach ($files as $file) {
            $messages = is_array($file) && is_array($file['messages'] ?? null) ? $file['messages'] : [];

            foreach ($messages as $message) {
                if (
                    is_array($message)
                    && ($message['identifier'] ?? null) === 'phpstan.dumpType'
                    && is_string($message['message'] ?? null)
                ) {
                    $types[] = str_replace('Dumped type: ', '', $message['message']);
                }
            }
        }

        return $types;
    }

    /**
     * Returns the ID of the process that wrote the first event, which is the main PHPStan process.
     *
     * @phpstan-param list<array{type: string, pid: int, defined: bool}> $events
     */
    private static function mainProcess(array $events): int
    {
        return $events[0]['pid'] ?? throw new RuntimeException('Event log is empty.');
    }

    /**
     * Reads the event log written by the generated bootstrap and configuration files.
     *
     * @phpstan-return list<array{type: string, pid: int, defined: bool}>
     */
    private function readEvents(): array
    {
        $events = [];

        foreach (explode("\n", trim($this->readFile(self::EVENT_LOG))) as $line) {
            $fields = explode(' ', trim($line));

            if (count($fields) < 2) {
                continue;
            }

            $events[] = [
                'type' => $fields[0],
                'pid' => (int) $fields[1],
                'defined' => ($fields[2] ?? '1') === '1',
            ];
        }

        return $events;
    }
}
