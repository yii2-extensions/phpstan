<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use yii\caching\{ArrayCache, FileCache};
use yii2\extensions\phpstan\ServiceMap;

use function array_filter;
use function array_values;
use function bin2hex;
use function count;
use function dirname;
use function explode;
use function fclose;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function is_dir;
use function is_resource;
use function is_string;
use function json_decode;
use function mkdir;
use function proc_close;
use function proc_open;
use function random_bytes;
use function realpath;
use function rmdir;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function strpos;
use function substr;
use function sys_get_temp_dir;
use function trim;
use function unlink;
use function var_export;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

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

    /**
     * Temporary directory owned by the current test, or an empty string before it is created.
     */
    private string $workDirectory = '';

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
        $temporaryDirectory = realpath(sys_get_temp_dir());

        if ($temporaryDirectory === false) {
            throw new RuntimeException(
                'System temporary directory is not available.',
            );
        }

        $workDirectory = $temporaryDirectory . DIRECTORY_SEPARATOR . self::TEMPORARY_PREFIX . bin2hex(random_bytes(8));

        if (mkdir($workDirectory . DIRECTORY_SEPARATOR . 'src', 0o700, true) === false) {
            throw new RuntimeException(
                sprintf('Temporary directory "%s" could not be created.', $workDirectory),
            );
        }

        $this->workDirectory = $workDirectory;

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

    protected function tearDown(): void
    {
        $workDirectory = $this->workDirectory;

        $this->workDirectory = '';

        $temporaryDirectory = realpath(sys_get_temp_dir());

        if (
            $workDirectory === ''
            || $temporaryDirectory === false
            || str_starts_with($workDirectory, $temporaryDirectory . DIRECTORY_SEPARATOR . self::TEMPORARY_PREFIX)
                === false
            || is_dir($workDirectory) === false
        ) {
            return;
        }

        $entries = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($workDirectory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var SplFileInfo $entry */
        foreach ($entries as $entry) {
            $entry->isDir() && $entry->isLink() === false
                ? rmdir($entry->getPathname())
                : unlink($entry->getPathname());
        }

        rmdir($workDirectory);
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
        $configuration = $this->path('phpstan.neon');
        $stdout = $this->path('stdout.txt');
        $stderr = $this->path('stderr.txt');

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

        $pipes = [];
        $process = proc_open(
            [
                PHP_BINARY,
                dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR
                    . 'phpstan',
                'analyse',
                '--configuration',
                $configuration,
                '--error-format=json',
                '--memory-limit=-1',
                '--no-progress',
                '-vvv',
                ...array_values($options),
            ],
            [0 => ['pipe', 'r'], 1 => ['file', $stdout, 'w'], 2 => ['file', $stderr, 'w']],
            $pipes,
            $this->workDirectory,
        );

        if (is_resource($process) === false) {
            throw new RuntimeException(
                'PHPStan process could not be started.',
            );
        }

        if (isset($pipes[0])) {
            fclose($pipes[0]);
        }

        $exitCode = proc_close($process);

        return [
            'exitCode' => $exitCode,
            'events' => $this->readEvents(),
            'stderr' => $this->readFile('stderr.txt'),
            'types' => self::dumpedTypes($this->readFile('stdout.txt')),
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

    private function neonPath(string $path): string
    {
        return "'" . str_replace(['\\', "'"], ['/', "''"], $path) . "'";
    }

    private function path(string $name): string
    {
        return $this->workDirectory . DIRECTORY_SEPARATOR . $name;
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

    private function readFile(string $name): string
    {
        $contents = file_get_contents($this->path($name));

        if ($contents === false) {
            throw new RuntimeException(
                sprintf('File "%s" could not be read.', $name),
            );
        }

        return $contents;
    }

    private function writeFile(string $name, string $contents): void
    {
        if (file_put_contents($this->path($name), $contents) === false) {
            throw new RuntimeException(
                sprintf('File "%s" could not be written.', $name),
            );
        }
    }
}
