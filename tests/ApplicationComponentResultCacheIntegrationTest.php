<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\ServiceMapResultCacheValueExtension;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function array_diff_assoc;
use function dirname;
use function explode;
use function preg_match_all;
use function sprintf;
use function var_export;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for the application component values that {@see ServiceMapResultCacheValueExtension} exposes to
 * the PHPStan result cache, across cold and warm runs of a real PHPStan process.
 *
 * The test generates its own project in a temporary directory: a stable NEON file, a stable configuration wrapper that
 * selects the Web application explicitly, requires a separate file returning only `components` and holds stable params,
 * and one file per consumer. The component whose definition changes and a stable component are each read through
 * `yii\di\ServiceLocator::get()` with a constant ID and through a `yii\BaseYii::$app` property; a control file reads a
 * local array shape without Yii. Only one component definition changes during the sequence, from
 * `yii\caching\ArrayCache` to `yii\caching\FileCache`. Each run reports file, line and identifier of every diagnostic,
 * plus the type shown by `PHPStan\dumpType()`; messages are not compared otherwise.
 *
 * The two routes record their dependency with different precision. `ServiceLocator::get()` records `component:<id>`
 * for the analysed file. A property read records it through PHPStan's `DeclarationDependencyTracker` against the
 * application class, since its reflection is reused for the rest of the process, so every file depending on that class
 * and finishing later in the same process may be reanalysed too. The changed run therefore requires both readers of
 * the changing component, and allows at most the other application property consumer besides them.
 */
final class ApplicationComponentResultCacheIntegrationTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Expected diagnostics of the control file, which no component change may alter.
     */
    private const array CONTROL = [
        ['src/Control.php', 14, 'phpstan.dumpType', 'string'],
        ['src/Control.php', 15, 'offsetAccess.notFound', null],
        ['src/Control.php', 15, 'phpstan.dumpType', '*ERROR*'],
    ];

    /**
     * Readers of the changing component plus the only other file depending on the application class, the stable
     * property consumer, which a class-level dependency may also reanalyse.
     */
    private const int MAXIMUM_REANALYSED = 3;

    /**
     * Number of files whose analysis reads the changing component: its locator and its property consumer.
     */
    private const int MINIMUM_REANALYSED = 2;

    /**
     * Expected diagnostics of the consumers of the component whose definition never changes.
     */
    private const array STABLE_CONSUMERS = [
        ['src/StableLocatorConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\DummyCache'],
        ['src/StableLocatorConsumer.php', 12, 'phpstan.dumpType', 'string'],
        ['src/StablePropertyConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\DummyCache'],
        ['src/StablePropertyConsumer.php', 12, 'phpstan.dumpType', 'string'],
    ];

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-component-result-cache-';

    public function testReanalyseComponentReadersWhenOnlyTheirDefinitionChanges(): void
    {
        $stableInputs = $this->stableInputHashes();

        $before = [
            ['src/ChangingLocatorConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\ArrayCache'],
            ['src/ChangingLocatorConsumer.php', 12, 'phpstan.dumpType', '*ERROR*'],
            ['src/ChangingLocatorConsumer.php', 12, 'property.notFound', null],
            ['src/ChangingPropertyConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\ArrayCache'],
            ['src/ChangingPropertyConsumer.php', 12, 'phpstan.dumpType', '*ERROR*'],
            ['src/ChangingPropertyConsumer.php', 12, 'property.notFound', null],
            ...self::CONTROL,
            ...self::STABLE_CONSUMERS,
        ];
        $after = [
            ['src/ChangingLocatorConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\FileCache'],
            ['src/ChangingLocatorConsumer.php', 12, 'phpstan.dumpType', 'string'],
            ['src/ChangingPropertyConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\FileCache'],
            ['src/ChangingPropertyConsumer.php', 12, 'phpstan.dumpType', 'string'],
            ...self::CONTROL,
            ...self::STABLE_CONSUMERS,
        ];

        $this->writeComponents('yii\caching\ArrayCache');

        $initialComponents = $this->readFile('project/components.php');
        $initialHash = self::hashFile($this->path('project/components.php'));
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
            'Initial run must infer both configured components on both routes and their diagnostics.',
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

        $this->writeComponents('yii\caching\FileCache');

        $changedHash = self::hashFile($this->path('project/components.php'));

        self::assertNotSame(
            $initialHash,
            $changedHash,
            'Components file must change its contents.',
        );
        self::assertSame(
            [5 => "    'changing' => ['class' => 'yii\\\\caching\\\\FileCache'],"],
            array_diff_assoc(
                explode("\n", $this->readFile('project/components.php')),
                explode("\n", $initialComponents),
            ),
            'Only the changing component line may differ.',
        );

        $changed = $this->analyse();
        $reanalysed = self::reanalysedFiles($changed['stderr'], 'Changed warm run');

        self::assertGreaterThanOrEqual(
            self::MINIMUM_REANALYSED,
            $reanalysed,
            'Both readers of the changing component must be reanalysed.',
        );
        self::assertLessThanOrEqual(
            self::MAXIMUM_REANALYSED,
            $reanalysed,
            'Only files depending on the changing component or on the application class may be reanalysed.',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $changed['stderr'],
            'Changed warm run must save the new results.',
        );
        self::assertSame(
            $after,
            $changed['results'],
            'Changing component readers must see the new class and lose their stale diagnostics.',
        );
        self::assertNotSame(
            self::resultsOf('src/ChangingLocatorConsumer.php', $before),
            self::resultsOf('src/ChangingLocatorConsumer.php', $changed['results']),
            'Locator consumer results must come from a new analysis.',
        );
        self::assertNotSame(
            self::resultsOf('src/ChangingPropertyConsumer.php', $before),
            self::resultsOf('src/ChangingPropertyConsumer.php', $changed['results']),
            'Property consumer results must come from a new analysis.',
        );
        self::assertSame(
            self::STABLE_CONSUMERS,
            [
                ...self::resultsOf('src/StableLocatorConsumer.php', $changed['results']),
                ...self::resultsOf('src/StablePropertyConsumer.php', $changed['results']),
            ],
            'Stable component consumer results must be unaffected by another component definition.',
        );
        self::assertSame(
            self::CONTROL,
            self::resultsOf('src/Control.php', $changed['results']),
            'Control results must be unaffected by the component definition.',
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
            self::hashFile($this->path('project/components.php')),
            'Final runs must use the changed components.',
        );
        self::assertSame(
            $stableInputs,
            $this->stableInputHashes(),
            'Only the components file may change during the sequence.',
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
                'components' => require __DIR__ . '/components.php',
                'params' => ['mode' => 'alpha'],
                'phpstan' => ['application_type' => 'yii\web\Application'],
            ];

            PHP,
        );
        $this->writeConsumer(
            'ChangingLocatorConsumer',
            '\yii\di\ServiceLocator $locator',
            "\$locator->get('changing')",
            'cachePath',
        );
        $this->writeConsumer(
            'ChangingPropertyConsumer',
            '',
            '\Yii::$app->changing',
            'cachePath',
        );
        $this->writeConsumer(
            'StableLocatorConsumer',
            '\yii\di\ServiceLocator $locator',
            "\$locator->get('stable')",
            'keyPrefix',
        );
        $this->writeConsumer(
            'StablePropertyConsumer',
            '',
            '\Yii::$app->stable',
            'keyPrefix',
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
     * Returns the number of files the run reanalysed after restoring the result cache, requiring a single restore and
     * the reuse of the cached results.
     */
    private static function reanalysedFiles(string $stderr, string $run): int
    {
        $matches = [];

        $count = preg_match_all(
            '/Result cache restored(?: in [\d.]+ seconds)?\. (\d+) files? will be reanalysed\./',
            $stderr,
            $matches,
        );

        self::assertSame(
            1,
            $count,
            sprintf("%s must restore the result cache once.\n%s", $run, $stderr),
        );
        self::assertStringNotContainsString(
            'Result cache not used',
            $stderr,
            sprintf('%s must reuse the cached results, not only the `tmpDir`.', $run),
        );

        return (int) $matches[1][0];
    }

    /**
     * Returns the hashes of every input of the analysis except the components file: the generated NEON file,
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
                'project/src/ChangingLocatorConsumer.php',
                'project/src/ChangingPropertyConsumer.php',
                'project/src/Control.php',
                'project/src/StableLocatorConsumer.php',
                'project/src/StablePropertyConsumer.php',
            ],
        );
    }

    /**
     * Writes the component definitions, the only file that changes during the sequence; the stable definition is always
     * written identically.
     */
    private function writeComponents(string $changingClass): void
    {
        $value = var_export($changingClass, true);

        $this->writeFile(
            'project/components.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            return [
                'changing' => ['class' => {$value}],
                'stable' => ['class' => 'yii\\caching\\DummyCache'],
            ];

            PHP,
        );
    }

    /**
     * Writes a consumer class that dumps the type of a component read and of one of its properties, on lines 11 and 12.
     *
     * @param string $parameter Parameter declaration of the read method, or an empty string.
     * @param string $read Expression reading the component.
     * @param string $property Property read on the component.
     */
    private function writeConsumer(string $className, string $parameter, string $read, string $property): void
    {
        $this->writeFile(
            "project/src/{$className}.php",
            <<<PHP
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class {$className}
            {
                public function read({$parameter}): void
                {
                    \\PHPStan\\dumpType({$read});
                    \\PHPStan\\dumpType({$read}->{$property});
                }
            }

            PHP,
        );
    }
}
