<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\provider\ContainerServiceBootstrapOnlyClassProvider;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function array_column;
use function array_filter;
use function class_exists;
use function count;
use function dirname;
use function explode;
use function fclose;
use function is_resource;
use function json_decode;
use function mkdir;
use function proc_close;
use function proc_open;
use function sprintf;
use function time;
use function trim;
use function var_export;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

/**
 * Integration tests for a container service ID naming a class that only `parameters.bootstrapFiles` loads, across cold
 * and warm runs of a real PHPStan process.
 *
 * The generated class lives outside the analysed paths and is loaded only by the bootstrap file. The bootstrap, the
 * {@see ServiceMap} configuration and the class file append their process ID to an event log, and the first two record
 * whether the class was already loaded.
 */
final class ContainerServiceBootstrapOnlyClassTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Class loaded only by the generated bootstrap file, and its container service ID.
     */
    private const string BOOTSTRAP_ONLY_CLASS = 'Probe\BootstrapOnlyService';

    /**
     * Expected diagnostics of the control file, which reads a local array shape without Yii.
     */
    private const array CONTROL = [
        ['src/Control.php', 14, 'phpstan.dumpType', 'string'],
        ['src/Control.php', 15, 'offsetAccess.notFound', null],
        ['src/Control.php', 15, 'phpstan.dumpType', '*ERROR*'],
    ];

    /**
     * Event log written by the generated bootstrap, configuration and class files.
     */
    private const string EVENT_LOG = 'project/events.log';

    /**
     * Expected diagnostics of the bootstrap-only service consumer when the configuration sees the class.
     */
    private const array RESOLVED_CONSUMER = [
        ['src/BootstrapOnlyConsumer.php', 11, 'phpstan.dumpType', 'Probe\BootstrapOnlyService'],
        ['src/BootstrapOnlyConsumer.php', 12, 'phpstan.dumpType', 'string'],
    ];

    /**
     * Expected diagnostics of the consumer of a service whose class is always available.
     */
    private const array STABLE_CONSUMER = [
        ['src/StableServiceConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\DummyCache'],
        ['src/StableServiceConsumer.php', 12, 'phpstan.dumpType', 'string'],
    ];

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-container-bootstrap-only-';

    /**
     * @phpstan-param list<array{string, int, string, string|null}> $consumer
     */
    #[DataProviderExternal(ContainerServiceBootstrapOnlyClassProvider::class, 'preBootstrapResultProvider')]
    public function testKeepPreBootstrapAnswerForBootstrapOnlyServiceOnColdAndWarmRuns(
        string $definition,
        array $consumer,
    ): void {
        $this->writeProject($definition, 'config.php');

        $inputs = $this->projectInputHashes();

        $expected = [
            ...$consumer,
            ...self::CONTROL,
            ...self::STABLE_CONSUMER,
        ];

        $coldStartedAt = time();

        [$cold, $coldEvents] = $this->analyseWithEvents();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $cold['stderr'],
            'Cold run must start without a result cache.',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $cold['stderr'],
            'Cold run must write the result cache.',
        );
        self::assertSame(
            $expected,
            $cold['results'],
            'Cold run must keep the answer given before the bootstrap.',
        );
        self::assertOnlyBootstrapLoadsClass(
            $coldEvents,
            true,
        );

        [$warm, $warmEvents] = $this->analyseWithEvents();

        $this->assertFirstWarmRunUnchanged(
            $warm,
            $expected,
            $coldStartedAt,
            'First warm run',
        );
        self::assertOnlyBootstrapLoadsClass(
            $warmEvents,
            false,
        );

        [$stableWarm, $stableWarmEvents] = $this->analyseWithEvents();

        self::assertRestored(
            0,
            $stableWarm['stderr'],
            'Second warm run',
        );
        self::assertStringContainsString(
            'Result cache was not rewritten because it is unchanged.',
            $stableWarm['stderr'],
            'Second warm run must keep the result cache as it is.',
        );
        self::assertSame(
            $expected,
            $stableWarm['results'],
            'Second warm run must restore the cold results.',
        );
        self::assertOnlyBootstrapLoadsClass(
            $stableWarmEvents,
            false,
        );
        self::assertSame(
            $inputs,
            $this->projectInputHashes(),
            'No analysis input may change during the sequence.',
        );
        self::assertClassNotLoadedByTestProcess();
    }

    /**
     * @phpstan-param list<array{string, int, string, string|null}> $consumer
     */
    #[DataProviderExternal(ContainerServiceBootstrapOnlyClassProvider::class, 'preBootstrapResultProvider')]
    public function testKeepPreBootstrapAnswerForBootstrapOnlyServiceWithoutWorkers(
        string $definition,
        array $consumer,
    ): void {
        $this->writeProject($definition, 'config.php');

        [$run, $runEvents] = $this->analyseWithEvents('--debug');

        self::assertStringContainsString(
            'Result cache not used because of debug mode.',
            $run['stderr'],
            'Debug run must not restore a result cache.',
        );
        self::assertSame(
            [...$consumer, ...self::CONTROL, ...self::STABLE_CONSUMER],
            $run['results'],
            'Single-process run must keep the answer given before the bootstrap.',
        );
        self::assertCount(
            1,
            self::eventsByProcess($runEvents),
            'Every event must come from the single analysis process.',
        );
        self::assertOnlyBootstrapLoadsClass(
            $runEvents,
            false,
        );
        self::assertClassNotLoadedByTestProcess();
    }

    #[DataProviderExternal(ContainerServiceBootstrapOnlyClassProvider::class, 'definitionProvider')]
    public function testResolveBootstrapOnlyServiceWhenTheConfigurationWrapperLoadsItFirst(string $definition): void
    {
        $this->writeProject($definition, 'config-wrapper.php');

        $inputs = $this->projectInputHashes();

        $expected = [
            ...self::RESOLVED_CONSUMER,
            ...self::CONTROL,
            ...self::STABLE_CONSUMER,
        ];

        $coldStartedAt = time();

        [$cold, $coldEvents] = $this->analyseWithEvents();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $cold['stderr'],
            'Cold run must start without a result cache.',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $cold['stderr'],
            'Cold run must write the result cache.',
        );
        self::assertSame(
            $expected,
            $cold['results'],
            'Cold run must infer the class the wrapper loaded.',
        );
        self::assertWrapperLoadsClassFirst(
            $coldEvents,
        );

        [$warm, $warmEvents] = $this->analyseWithEvents();

        $this->assertFirstWarmRunUnchanged(
            $warm,
            $expected,
            $coldStartedAt,
            'First warm run',
        );
        self::assertWrapperLoadsClassFirst(
            $warmEvents,
        );
        self::assertSame(
            $inputs,
            $this->projectInputHashes(),
            'No analysis input may change during the sequence.',
        );
        self::assertClassNotLoadedByTestProcess();
    }

    /**
     * @phpstan-param array{service: null, unresolved: bool, value: string} $beforeBootstrap
     */
    #[DataProviderExternal(ContainerServiceBootstrapOnlyClassProvider::class, 'preBootstrapAnswerProvider')]
    public function testServiceMapAnswerForBootstrapOnlyIdDependsOnWhenItsClassLoads(
        string $definition,
        array $beforeBootstrap,
    ): void {
        $this->writeProject($definition, 'config.php');

        $resolved = [
            'service' => self::BOOTSTRAP_ONLY_CLASS,
            'unresolved' => false,
            'value' => self::BOOTSTRAP_ONLY_CLASS,
        ];

        self::assertSame(
            ['loaded' => false, ...$beforeBootstrap],
            $this->probeServiceMap('project/config.php', ''),
            'Map built before the bootstrap must keep the ID out of the resolved services.',
        );
        self::assertSame(
            ['loaded' => true, ...$resolved],
            $this->probeServiceMap('project/config.php', 'project/bootstrap.php'),
            'Map built after the bootstrap must resolve the ID as its class.',
        );
        self::assertSame(
            ['loaded' => false, ...$resolved],
            $this->probeServiceMap('project/config-wrapper.php', ''),
            'Wrapper must load the class for the map without a bootstrap.',
        );
        self::assertClassNotLoadedByTestProcess();
    }

    protected function setUp(): void
    {
        $this->createWorkDirectory();

        if (mkdir($this->path('project/lib'), 0o700) === false) {
            throw new RuntimeException(
                'Directory for the bootstrap-only class could not be created.',
            );
        }

        $this->writeFile(
            'project/lib/BootstrapOnlyService.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            file_put_contents(dirname(__DIR__) . '/events.log', 'class ' . getmypid() . "\n", FILE_APPEND | LOCK_EX);

            final class BootstrapOnlyService
            {
                public string $label = 'default';

                public function describe(): string
                {
                    return $this->label;
                }
            }

            PHP,
        );
        $this->writeFile(
            'project/bootstrap.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            file_put_contents(
                __DIR__ . '/events.log',
                'bootstrap ' . getmypid() . ' ' . (class_exists('Probe\BootstrapOnlyService', false) ? '1' : '0') . "\n",
                FILE_APPEND | LOCK_EX,
            );

            require_once __DIR__ . '/lib/BootstrapOnlyService.php';

            PHP,
        );
        $this->writeFile(
            'project/config-wrapper.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            require_once __DIR__ . '/lib/BootstrapOnlyService.php';

            return require __DIR__ . '/config.php';

            PHP,
        );
        $this->writeFile(
            'project/src/BootstrapOnlyConsumer.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class BootstrapOnlyConsumer
            {
                public function read(\yii\di\Container $container): void
                {
                    \PHPStan\dumpType($container->get('Probe\BootstrapOnlyService'));
                    \PHPStan\dumpType($container->get('Probe\BootstrapOnlyService')->describe());
                }
            }

            PHP,
        );
        $this->writeFile(
            'project/src/StableServiceConsumer.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class StableServiceConsumer
            {
                public function read(\yii\di\Container $container): void
                {
                    \PHPStan\dumpType($container->get('cache.stable'));
                    \PHPStan\dumpType($container->get('cache.stable')->keyPrefix);
                }
            }

            PHP,
        );
        $this->writeFile(
            'project/src/Control.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class Control
            {
                /**
                 * @param array{mode: string} $values
                 */
                public function read(array $values): void
                {
                    \PHPStan\dumpType($values['mode']);
                    \PHPStan\dumpType($values['extra']);
                }
            }

            PHP,
        );

        self::assertClassNotLoadedByTestProcess();
    }

    /**
     * Runs PHPStan with an empty event log and returns its run and the events it wrote.
     *
     * @phpstan-return array{
     *     array{stderr: string, results: list<array{string, int, string, string|null}>},
     *     list<array{type: string, pid: int, loaded: bool}>,
     * }
     */
    private function analyseWithEvents(string ...$options): array
    {
        $this->writeFile(
            self::EVENT_LOG,
            '',
        );

        $run = $this->analyse(...$options);

        return [$run, $this->readEvents()];
    }

    private static function assertClassNotLoadedByTestProcess(): void
    {
        self::assertFalse(
            class_exists(self::BOOTSTRAP_ONLY_CLASS, false),
            'PHPUnit process must never load the bootstrap-only class.',
        );
    }

    /**
     * Asserts that the configuration runs first without the class, that each process loads the class only from its
     * bootstrap and that the main process runs the bootstrap; on a cold run, a worker must also run it.
     *
     * @phpstan-param list<array{type: string, pid: int, loaded: bool}> $events
     */
    private static function assertOnlyBootstrapLoadsClass(array $events, bool $workerRunsBootstrap): void
    {
        $first = $events[0] ?? null;

        self::assertNotNull(
            $first,
            'Event log must not be empty.',
        );
        self::assertSame(
            ['type' => 'config', 'loaded' => false],
            ['type' => $first['type'], 'loaded' => $first['loaded']],
            'Configuration must run first, before the class exists.',
        );

        $mainProcess = $first['pid'];

        $processes = self::eventsByProcess($events);

        foreach ($processes as $processEvents) {
            $bootstrapped = false;

            foreach ($processEvents as $position => $event) {
                if ($event['type'] === 'class') {
                    self::assertSame(
                        'bootstrap',
                        $processEvents[$position - 1]['type'] ?? null,
                        'Class must be loaded by the bootstrap of the same process.',
                    );

                    continue;
                }

                if ($bootstrapped === false) {
                    self::assertFalse(
                        $event['loaded'],
                        sprintf('Class must not exist when the first %s runs.', $event['type']),
                    );
                }

                if ($event['type'] === 'bootstrap') {
                    $bootstrapped = true;

                    self::assertSame(
                        'class',
                        $processEvents[$position + 1]['type'] ?? null,
                        'Bootstrap must load the class.',
                    );
                }
            }
        }

        self::assertContains(
            'bootstrap',
            array_column($processes[$mainProcess] ?? [], 'type'),
            'Main process must run the bootstrap after the configuration.',
        );

        if ($workerRunsBootstrap) {
            self::assertNotSame(
                [],
                array_filter(
                    $events,
                    static fn(array $event): bool => $event['type'] === 'bootstrap' && $event['pid'] !== $mainProcess,
                ),
                'A worker process must run the bootstrap files.',
            );
        }
    }

    /**
     * Asserts that the wrapper loads the class before the main process configuration, and that every configuration and
     * bootstrap execution sees it.
     *
     * @phpstan-param list<array{type: string, pid: int, loaded: bool}> $events
     */
    private static function assertWrapperLoadsClassFirst(array $events): void
    {
        $first = $events[0] ?? null;

        self::assertNotNull(
            $first,
            'Event log must not be empty.',
        );
        self::assertSame(
            'class',
            $first['type'],
            'Wrapper must load the class before anything else runs.',
        );

        $processes = self::eventsByProcess($events);

        foreach ($processes as $processEvents) {
            foreach ($processEvents as $position => $event) {
                if ($event['type'] === 'class') {
                    self::assertSame(
                        0,
                        $position,
                        'Class must be loaded before any configuration or bootstrap of the process.',
                    );
                } else {
                    self::assertTrue(
                        $event['loaded'],
                        sprintf('Every %s execution must see the class.', $event['type']),
                    );
                }
            }
        }

        self::assertContains(
            'bootstrap',
            array_column($processes[$first['pid']] ?? [], 'type'),
            'Main process must still run the bootstrap.',
        );
    }

    /**
     * Groups the events by process ID, keeping their order.
     *
     * @phpstan-param list<array{type: string, pid: int, loaded: bool}> $events
     *
     * @phpstan-return array<int, list<array{type: string, pid: int, loaded: bool}>>
     */
    private static function eventsByProcess(array $events): array
    {
        $processes = [];

        foreach ($events as $event) {
            $processes[$event['pid']][] = $event;
        }

        return $processes;
    }

    /**
     * Builds a {@see ServiceMap} in a separate PHP process, after the given bootstrap when one is passed, and returns
     * whether the class was loaded before the map was built and the map answers for its ID.
     *
     * @phpstan-return array<mixed>
     */
    private function probeServiceMap(string $configFile, string $bootstrapFile): array
    {
        $pipes = [];

        $process = proc_open(
            [
                PHP_BINARY,
                $this->path('probe.php'),
                $this->path($configFile),
                $bootstrapFile === '' ? '' : $this->path($bootstrapFile),
            ],
            [
                0 => ['pipe', 'r'],
                1 => ['file', $this->path('output/probe-stdout.txt'), 'w'],
                2 => ['file', $this->path('output/probe-stderr.txt'), 'w'],
            ],
            $pipes,
            $this->path('project'),
        );

        if (is_resource($process) === false) {
            throw new RuntimeException(
                'Service map probe could not be started.',
            );
        }

        if (isset($pipes[0])) {
            fclose($pipes[0]);
        }

        self::assertSame(
            0,
            proc_close($process),
            "Service map probe must finish without errors.\n" . $this->readFile('output/probe-stderr.txt'),
        );

        $answer = json_decode($this->readFile('output/probe-stdout.txt'), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray(
            $answer,
            'Service map probe must print a JSON object.',
        );

        return $answer;
    }

    /**
     * Returns the hashes of every generated input of the analysis, and of the included extension configuration and
     * stubs.
     *
     * @phpstan-return array<string, string>
     */
    private function projectInputHashes(): array
    {
        return $this->inputHashes(
            [
                'project/phpstan.neon',
                'project/bootstrap.php',
                'project/config.php',
                'project/config-wrapper.php',
                'project/lib/BootstrapOnlyService.php',
                'project/src/BootstrapOnlyConsumer.php',
                'project/src/Control.php',
                'project/src/StableServiceConsumer.php',
            ],
        );
    }

    /**
     * Reads the event log; bootstrap and configuration lines record whether the class was loaded.
     *
     * @phpstan-return list<array{type: string, pid: int, loaded: bool}>
     */
    private function readEvents(): array
    {
        $events = [];

        foreach (explode("\n", trim($this->readFile(self::EVENT_LOG))) as $line) {
            $fields = explode(' ', $line);

            if (count($fields) < 2) {
                throw new RuntimeException(
                    sprintf('Unexpected event log line "%s".', $line),
                );
            }

            $events[] = [
                'type' => $fields[0],
                'pid' => (int) $fields[1],
                'loaded' => ($fields[2] ?? '') === '1',
            ];
        }

        return $events;
    }

    /**
     * Writes the NEON file, the configuration with the given definition under the bootstrap-only ID, and the service
     * map probe.
     */
    private function writeProject(string $definition, string $configFile): void
    {
        $class = var_export(self::BOOTSTRAP_ONLY_CLASS, true);
        $autoload = var_export(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php',
            true,
        );

        $this->writeFile(
            'project/phpstan.neon',
            <<<NEON
            includes:
                - {$this->neonPath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'extension.neon')}

            parameters:
                level: max
                paths:
                    - {$this->neonPath($this->path('project/src'))}
                tmpDir: {$this->neonPath($this->path('tmp'))}
                bootstrapFiles:
                    - {$this->neonPath($this->path('project/bootstrap.php'))}
                stubFiles:
                    - {$this->neonPath(self::applicationClassesStub())}
                yii2:
                    config_path: {$this->neonPath($this->path('project/' . $configFile))}

            NEON,
        );
        // params keep project stub validation from reporting the plain `Module::$params` array at level max
        $this->writeFile(
            'project/config.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            file_put_contents(
                __DIR__ . '/events.log',
                'config ' . getmypid() . ' ' . (class_exists({$class}, false) ? '1' : '0') . "\\n",
                FILE_APPEND | LOCK_EX,
            );

            return [
                'container' => [
                    'definitions' => [
                        {$class} => {$definition},
                        'cache.stable' => 'yii\\caching\\DummyCache',
                    ],
                ],
                'params' => ['mode' => 'alpha'],
            ];

            PHP,
        );
        $this->writeFile(
            'probe.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            use yii2\\extensions\\phpstan\\{ServiceMap, ServiceMapResultCacheValueExtension};

            require {$autoload};

            if (\$argv[2] !== '') {
                require_once \$argv[2];
            }

            \$loaded = class_exists({$class}, false);
            \$map = new ServiceMap(\$argv[1]);

            echo json_encode(
                [
                    'loaded' => \$loaded,
                    'service' => \$map->getServiceById({$class}),
                    'unresolved' => \$map->isUnresolvedService({$class}),
                    'value' => (new ServiceMapResultCacheValueExtension(\$map))->getValue(
                        ServiceMapResultCacheValueExtension::serviceKey({$class}),
                    ),
                ],
                JSON_THROW_ON_ERROR,
            );

            PHP,
        );
    }
}
