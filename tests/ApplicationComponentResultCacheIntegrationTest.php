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
 * the PHPStan result cache across cold and warm runs.
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
     * Readers of the changing component plus the stable property consumer, which shares the application class
     * dependency that PHPStan tracks per class.
     */
    private const int MAXIMUM_REANALYSED = 3;

    /**
     * Readers of the changing component: its locator and property consumers.
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
                bootstrapFiles:
                    - {$this->neonPath(self::yiiBootstrapFile())}
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
     * Returns the number of files reanalysed after a single result cache restore.
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
     * Returns the hashes of every analysis input except the components file.
     *
     * @phpstan-return array<string, string>
     */
    private function stableInputHashes(): array
    {
        return [
            ...$this->inputHashes(
                [
                    'project/phpstan.neon',
                    'project/config.php',
                    'project/src/ChangingLocatorConsumer.php',
                    'project/src/ChangingPropertyConsumer.php',
                    'project/src/Control.php',
                    'project/src/StableLocatorConsumer.php',
                    'project/src/StablePropertyConsumer.php',
                ],
            ),
            'vendor/yiisoft/yii2/Yii.php' => self::hashFile(self::yiiBootstrapFile()),
        ];
    }

    /**
     * Writes the component definitions, the only file that changes.
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
     * Writes a consumer that dumps a component read and one of its properties on lines 11 and 12.
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

    /**
     * Returns Yii's `Yii.php`, which Yii 2.0.55 doesn't classmap.
     */
    private static function yiiBootstrapFile(): string
    {
        return dirname(__DIR__)
            . DIRECTORY_SEPARATOR
            . 'vendor'
            . DIRECTORY_SEPARATOR
            . 'yiisoft'
            . DIRECTORY_SEPARATOR
            . 'yii2'
            . DIRECTORY_SEPARATOR
            . 'Yii.php';
    }
}
