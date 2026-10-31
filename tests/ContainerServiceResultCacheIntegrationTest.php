<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\ServiceMapResultCacheValueExtension;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function array_diff_assoc;
use function dirname;
use function explode;
use function var_export;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for the container service values that {@see ServiceMapResultCacheValueExtension} exposes to the
 * PHPStan result cache, across cold and warm runs of a real PHPStan process.
 *
 * The test generates its own project in a temporary directory: a stable NEON file, a stable configuration wrapper that
 * requires a separate file returning only `container.definitions`, a consumer of `yii\di\Container::get()` for the ID
 * whose definition changes, a consumer of another ID whose definition stays the same, and a control file that reads a
 * local array shape without Yii. Only one service definition changes during the sequence, from
 * `yii\caching\ArrayCache` to `yii\caching\FileCache`. Each run reports file, line and identifier of every diagnostic,
 * plus the type shown by `PHPStan\dumpType()`; messages are not compared otherwise.
 */
final class ContainerServiceResultCacheIntegrationTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Expected diagnostics of the control file, which no service change may alter.
     */
    private const array CONTROL = [
        ['src/Control.php', 14, 'phpstan.dumpType', 'string'],
        ['src/Control.php', 15, 'offsetAccess.notFound', null],
        ['src/Control.php', 15, 'phpstan.dumpType', '*ERROR*'],
    ];

    /**
     * Expected diagnostics of the consumer of the service whose definition never changes.
     */
    private const array STABLE_CONSUMER = [
        ['src/StableServiceConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\DummyCache'],
        ['src/StableServiceConsumer.php', 12, 'phpstan.dumpType', 'string'],
    ];

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-container-result-cache-';

    public function testReanalyseServiceConsumerOnlyWhenItsDefinitionChanges(): void
    {
        $stableInputs = $this->stableInputHashes();

        $before = [
            ['src/ChangingServiceConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\ArrayCache'],
            ['src/ChangingServiceConsumer.php', 12, 'phpstan.dumpType', '*ERROR*'],
            ['src/ChangingServiceConsumer.php', 12, 'property.notFound', null],
            ...self::CONTROL,
            ...self::STABLE_CONSUMER,
        ];
        $after = [
            ['src/ChangingServiceConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\FileCache'],
            ['src/ChangingServiceConsumer.php', 12, 'phpstan.dumpType', 'string'],
            ...self::CONTROL,
            ...self::STABLE_CONSUMER,
        ];

        $this->writeDefinitions('yii\caching\ArrayCache');

        $initialDefinitions = $this->readFile('project/definitions.php');
        $initialHash = self::hashFile($this->path('project/definitions.php'));
        $initialCold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $initialCold['stderr'],
            'Initial run must start without a result cache.',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $initialCold['stderr'],
            'Initial run must write the result cache.',
        );
        self::assertFileExists(
            $this->path('tmp/resultCache.php'),
            'Result cache file must be created in the owned `tmpDir`.',
        );
        self::assertSame(
            $before,
            $initialCold['results'],
            'Initial run must infer both configured services and their diagnostics.',
        );

        $initialWarm = $this->analyse();

        self::assertRestored(
            0,
            $initialWarm['stderr'],
            'Unchanged initial warm run',
        );
        self::assertStringContainsString(
            'Result cache was not rewritten because it is unchanged.',
            $initialWarm['stderr'],
            'Unchanged initial warm run must keep the result cache as it is.',
        );
        self::assertSame(
            $before,
            $initialWarm['results'],
            'Unchanged initial warm run must restore the initial results.',
        );

        $this->writeDefinitions('yii\caching\FileCache');

        $changedHash = self::hashFile($this->path('project/definitions.php'));

        self::assertNotSame(
            $initialHash,
            $changedHash,
            'Definitions file must change its contents.',
        );
        self::assertSame(
            [5 => "    'cache.changing' => 'yii\\\\caching\\\\FileCache',"],
            array_diff_assoc(
                explode("\n", $this->readFile('project/definitions.php')),
                explode("\n", $initialDefinitions),
            ),
            'Only the changing definition line may differ.',
        );

        $changed = $this->analyse();

        self::assertRestored(
            1,
            $changed['stderr'],
            'Changed warm run',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $changed['stderr'],
            'Changed warm run must save the new results.',
        );
        self::assertSame(
            $after,
            $changed['results'],
            'Changing consumer must see the new class and lose its stale diagnostic.',
        );
        self::assertNotSame(
            self::resultsOf('src/ChangingServiceConsumer.php', $before),
            self::resultsOf('src/ChangingServiceConsumer.php', $changed['results']),
            'Changing consumer results must come from a new analysis, so it is the reanalysed file.',
        );
        self::assertSame(
            self::STABLE_CONSUMER,
            self::resultsOf('src/StableServiceConsumer.php', $changed['results']),
            'Stable service consumer results must be unaffected by another service definition.',
        );
        self::assertSame(
            self::CONTROL,
            self::resultsOf('src/Control.php', $changed['results']),
            'Control results must be unaffected by the service definition.',
        );

        $changedWarm = $this->analyse();

        self::assertRestored(
            0,
            $changedWarm['stderr'],
            'Unchanged changed warm run',
        );
        self::assertStringContainsString(
            'Result cache was not rewritten because it is unchanged.',
            $changedWarm['stderr'],
            'Unchanged changed warm run must keep the result cache as it is.',
        );
        self::assertSame(
            $after,
            $changedWarm['results'],
            'Unchanged changed warm run must restore the changed results.',
        );

        $this->removeTemporaryCacheDirectory();

        $finalCold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $finalCold['stderr'],
            'Final run must start without a result cache.',
        );
        self::assertSame(
            $changed['results'],
            $finalCold['results'],
            'Final cold results must match the results after detecting the change.',
        );
        self::assertSame(
            $changedHash,
            self::hashFile($this->path('project/definitions.php')),
            'Final runs must use the changed definitions.',
        );
        self::assertSame(
            $stableInputs,
            $this->stableInputHashes(),
            'Only the definitions file may change during the sequence.',
        );
    }

    protected function setUp(): void
    {
        $this->createWorkDirectory();

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
                stubFiles:
                    - {$this->neonPath(self::applicationClassesStub())}
                yii2:
                    config_path: {$this->neonPath($this->path('project/config.php'))}

            NEON,
        );
        // params keep project stub validation from reporting the plain `Module::$params` array at level max
        $this->writeFile(
            'project/config.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            return [
                'container' => ['definitions' => require __DIR__ . '/definitions.php'],
                'params' => ['mode' => 'alpha'],
            ];

            PHP,
        );
        $this->writeFile(
            'project/src/ChangingServiceConsumer.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class ChangingServiceConsumer
            {
                public function read(\yii\di\Container $container): void
                {
                    \PHPStan\dumpType($container->get('cache.changing'));
                    \PHPStan\dumpType($container->get('cache.changing')->cachePath);
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
    }

    /**
     * Returns the hashes of every input of the analysis except the definitions file: the generated NEON file,
     * configuration wrapper, consumers and control file, and the included extension configuration and stubs.
     *
     * @phpstan-return array<string, string>
     */
    private function stableInputHashes(): array
    {
        return $this->inputHashes(
            [
                'project/phpstan.neon',
                'project/config.php',
                'project/src/ChangingServiceConsumer.php',
                'project/src/Control.php',
                'project/src/StableServiceConsumer.php',
            ],
        );
    }

    /**
     * Writes the container definitions, the only file that changes during the sequence; the stable definition is always
     * written identically.
     */
    private function writeDefinitions(string $changingClass): void
    {
        $value = var_export($changingClass, true);

        $this->writeFile(
            'project/definitions.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            return [
                'cache.changing' => {$value},
                'cache.stable' => 'yii\\caching\\DummyCache',
            ];

            PHP,
        );
    }
}
