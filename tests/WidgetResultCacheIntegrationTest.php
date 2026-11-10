<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\rules\{WidgetInvalidOptionTypeRule, WidgetUnknownOptionRule};
use yii2\extensions\phpstan\tests\provider\WidgetResultCacheIntegrationProvider;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function dirname;
use function implode;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for the container, called class and created class dependencies of {@see WidgetUnknownOptionRule}
 * and {@see WidgetInvalidOptionTypeRule} across cold and warm result cache runs.
 *
 * {@see WidgetResultCacheIntegrationProvider} for test case data providers.
 */
final class WidgetResultCacheIntegrationTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Expected diagnostics of the consumer of `Probe\Target`, by the rules that report it.
     */
    private const array CONSUMER = [
        'both' => [
            ['src/Consumer.php', 11, 'yii2.widget.invalidOptionType', null],
            ['src/Consumer.php', 11, 'yii2.widget.unknownOption', null],
        ],
        'none' => [],
        'type' => [
            ['src/Consumer.php', 11, 'yii2.widget.invalidOptionType', null],
        ],
        'unknown' => [
            ['src/Consumer.php', 11, 'yii2.widget.unknownOption', null],
        ],
    ];

    /**
     * Expected diagnostics of the consumer of the unbound `Probe\Stable`, which no change may alter.
     */
    private const array STABLE_CONSUMER = [
        ['src/StableConsumer.php', 11, 'yii2.widget.unknownOption', null],
    ];

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-widget-result-cache-';

    /**
     * @phpstan-param list<string> $definitions
     */
    #[DataProviderExternal(WidgetResultCacheIntegrationProvider::class, 'declarationProvider')]
    public function testFollowDeclarationChangesOfCalledAndCreatedClasses(
        array $definitions,
        string $initialTarget,
        string $changedTarget,
        string $initialOther,
        string $changedOther,
        int $reanalysed,
        string $initialResults,
        string $changedResults,
    ): void {
        $this->writeContainer($definitions);
        $this->writeWidget('Target', 'public int $count = 0;', $initialTarget);
        $this->writeWidget('Other', 'public mixed $count = null;', $initialOther);

        $this->assertSequence(
            self::results($initialResults),
            self::results($changedResults),
            $reanalysed,
            function () use ($changedTarget, $changedOther): void {
                $this->writeWidget('Target', 'public int $count = 0;', $changedTarget);
                $this->writeWidget('Other', 'public mixed $count = null;', $changedOther);
            },
            ['project/container.php', 'project/src/Consumer.php', 'project/src/StableConsumer.php'],
        );
    }

    /**
     * @phpstan-param list<string> $initialDefinitions
     * @phpstan-param list<string> $changedDefinitions
     */
    #[DataProviderExternal(WidgetResultCacheIntegrationProvider::class, 'containerProvider')]
    public function testReanalyseConsumerWhenContainerBindingChanges(
        array $initialDefinitions,
        array $changedDefinitions,
        bool $initialReported,
        bool $changedReported,
    ): void {
        $this->writeContainer($initialDefinitions);
        $this->writeWidget('Target', 'public int $count = 0;', '');
        $this->writeWidget('Other', 'public mixed $count = null;', "    public string \$colour = '';\n");

        $this->assertSequence(
            self::results($initialReported ? 'both' : 'none'),
            self::results($changedReported ? 'both' : 'none'),
            1,
            function () use ($changedDefinitions): void {
                $this->writeContainer($changedDefinitions);
            },
            [
                'project/src/Consumer.php',
                'project/src/Other.php',
                'project/src/StableConsumer.php',
                'project/src/Target.php',
            ],
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
                    rules:
                        widgetInvalidOptionType: true
                        widgetUnknownOption: true

            NEON,
        );
        // params keep project stub validation from reporting the plain `Module::$params` array at level max
        $this->writeFile(
            'project/config.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            return [
                'container' => require __DIR__ . '/container.php',
                'params' => ['mode' => 'alpha'],
            ];

            PHP,
        );
        $this->writeFile(
            'project/src/Plain.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class Plain extends \yii\base\BaseObject
            {
                public int $count = 0;
            }

            PHP,
        );
        $this->writeWidget('Stable', 'public int $count = 0;', '');

        $consumers = [
            'Consumer' => "Target::widget(['colour' => 'red', 'count' => 'all'])",
            'StableConsumer' => "Stable::begin(['shade' => 'dark'])",
        ];

        foreach ($consumers as $consumer => $call) {
            $this->writeFile(
                "project/src/{$consumer}.php",
                <<<PHP
                <?php

                declare(strict_types=1);

                namespace Probe;

                final class {$consumer}
                {
                    public function render(): void
                    {
                        {$call};
                    }
                }

                PHP,
            );
        }
    }

    /**
     * Runs the cold, warm, changed, changed warm and final cold analyses around one change, asserting the results of
     * each, the number of files the changed run reanalyses, and that only the changed inputs differ.
     *
     * @phpstan-param list<array{string, int, string, string|null}> $before
     * @phpstan-param list<array{string, int, string, string|null}> $after
     * @phpstan-param callable(): void $change
     * @phpstan-param list<string> $stableFiles
     */
    private function assertSequence(
        array $before,
        array $after,
        int $reanalysed,
        callable $change,
        array $stableFiles,
    ): void {
        $stableFiles = ['project/phpstan.neon', 'project/config.php', 'project/src/Plain.php', ...$stableFiles];

        $stableInputs = $this->inputHashes($stableFiles);

        $initialCold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $initialCold['stderr'],
            'Initial run must start without a result cache.',
        );
        self::assertSame(
            $before,
            $initialCold['results'],
            'Initial run must report the initial results.',
        );

        $this->assertWarmRunUnchanged(
            $this->analyse(),
            $before,
            'Unchanged initial warm run',
        );

        $change();

        $changed = $this->analyse();

        self::assertRestored(
            $reanalysed,
            $changed['stderr'],
            'Changed warm run',
        );
        self::assertSame(
            $after,
            $changed['results'],
            'Consumer must follow the change: report while the call is proven, stand aside otherwise.',
        );

        $this->assertWarmRunUnchanged(
            $this->analyse(),
            $after,
            'Unchanged changed warm run',
        );

        $this->removeTemporaryCacheDirectory();

        $finalCold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $finalCold['stderr'],
            'Final run must start without a result cache.',
        );
        self::assertSame(
            $after,
            $finalCold['results'],
            'Final cold results must match the results after detecting the change.',
        );
        self::assertSame(
            $stableInputs,
            $this->inputHashes($stableFiles),
            'Only the changed inputs may differ during the sequence.',
        );
    }

    /**
     * Returns the expected results for the rules that report the consumer of `Probe\Target`.
     *
     * @phpstan-return list<array{string, int, string, string|null}>
     */
    private static function results(string $reported): array
    {
        return [...self::CONSUMER[$reported] ?? [], ...self::STABLE_CONSUMER];
    }

    /**
     * Writes the container section with the given definition entries, after one unrelated definition.
     *
     * @phpstan-param list<string> $definitions
     */
    private function writeContainer(array $definitions): void
    {
        $definitionLines = implode("\n        ", ["'cache.stable' => 'yii\\caching\\DummyCache',", ...$definitions]);

        $this->writeFile(
            'project/container.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            return [
                'definitions' => [
                    {$definitionLines}
                ],
            ];

            PHP,
        );
    }

    /**
     * Writes the widget `Probe\<name>` with one property and the given extra members.
     */
    private function writeWidget(string $name, string $property, string $members): void
    {
        $members = $members === '' ? '' : "\n{$members}";

        $this->writeFile(
            "project/src/{$name}.php",
            <<<PHP
            <?php

            declare(strict_types=1);

            namespace Probe;

            class {$name} extends \\yii\\base\\Widget
            {
                {$property}
            {$members}}

            PHP,
        );
    }
}
