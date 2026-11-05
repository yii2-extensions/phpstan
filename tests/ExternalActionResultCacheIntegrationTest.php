<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\{DataProviderExternal, RequiresPhp};
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\rules\{ExternalActionInvalidOptionTypeRule, ExternalActionUnknownOptionRule};
use yii2\extensions\phpstan\tests\provider\ExternalActionResultCacheIntegrationProvider;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function dirname;
use function implode;
use function str_replace;
use function time;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for the container, created class, behavior and controller dependencies of
 * {@see ExternalActionUnknownOptionRule} and {@see ExternalActionInvalidOptionTypeRule} across result cache runs.
 *
 * {@see ExternalActionResultCacheIntegrationProvider} for test case data providers.
 */
final class ExternalActionResultCacheIntegrationTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Expected diagnostics of `Probe\ProbeController`, by the rules that report it.
     */
    private const array CONTROLLER = [
        'both' => [
            ['src/ProbeController.php', 11, 'yii2.externalAction.invalidOptionType', null],
            ['src/ProbeController.php', 11, 'yii2.externalAction.unknownOption', null],
        ],
        'none' => [],
        'type' => [
            ['src/ProbeController.php', 11, 'yii2.externalAction.invalidOptionType', null],
        ],
        'unknown' => [
            ['src/ProbeController.php', 11, 'yii2.externalAction.unknownOption', null],
        ],
    ];

    /**
     * Expected diagnostics of `Probe\StableController`, which no change may alter.
     */
    private const array STABLE_CONTROLLER = [
        ['src/StableController.php', 11, 'yii2.externalAction.unknownOption', null],
    ];

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-external-action-result-cache-';

    #[DataProviderExternal(ExternalActionResultCacheIntegrationProvider::class, 'boundClassProvider')]
    public function testFollowDeclarationOfClassReachedOnlyThroughConfiguration(
        string $initialMembers,
        string $changedMembers,
        string $initialResults,
        string $changedResults,
    ): void {
        $this->writeContainer(["'Probe\\Target' => 'Probe\\Other',"]);
        $this->writeAction('Other', 'public mixed $count = null;', $initialMembers);

        $this->assertSequence(
            self::results($initialResults),
            self::results($changedResults),
            2,
            function () use ($changedMembers): void {
                $this->writeAction('Other', 'public mixed $count = null;', $changedMembers);
            },
            ['project/container.php', 'project/behaviors.php', 'project/src/BaseController.php'],
        );
    }

    /**
     * @phpstan-param list<string> $initialDefinitions
     * @phpstan-param list<string> $changedDefinitions
     */
    #[DataProviderExternal(ExternalActionResultCacheIntegrationProvider::class, 'containerProvider')]
    public function testReanalyseControllerWhenContainerBindingChanges(
        array $initialDefinitions,
        array $changedDefinitions,
        string $initialResults,
        string $changedResults,
    ): void {
        $this->writeContainer($initialDefinitions);
        $this->writeAction('Other', 'public mixed $count = null;', "    public string \$colour = '';\n");

        $this->assertSequence(
            self::results($initialResults),
            self::results($changedResults),
            1,
            function () use ($changedDefinitions): void {
                $this->writeContainer($changedDefinitions);
            },
            ['project/behaviors.php', 'project/src/BaseController.php', 'project/src/Other.php'],
        );
    }

    #[DataProviderExternal(ExternalActionResultCacheIntegrationProvider::class, 'parentControllerProvider')]
    public function testReanalyseControllerWhenParentChangesCreateAction(
        string $initialMembers,
        string $changedMembers,
        string $initialResults,
        string $changedResults,
    ): void {
        $this->writeContainer([]);
        $this->writeBaseController($initialMembers);

        $this->assertSequence(
            self::results($initialResults),
            self::results($changedResults),
            2,
            function () use ($changedMembers): void {
                $this->writeBaseController($changedMembers);
            },
            ['project/container.php', 'project/behaviors.php'],
        );
    }

    #[RequiresPhp('>= 8.4')]
    public function testStandAsideWhenActionStartsHookingId(): void
    {
        $this->writeFile(
            'project/phpstan.neon',
            str_replace('    level: max', "    level: max\n    phpVersion: 80400", $this->readFile('project/phpstan.neon')),
        );
        $this->writeContainer([]);

        $this->assertSequence(
            self::results('both'),
            self::results('none'),
            2,
            function (): void {
                $this->writeAction('Target', 'public int $count = 0;', "    public \$id {\n        get => \$this->id;\n    }\n");
            },
            ['project/container.php', 'project/behaviors.php', 'project/src/BaseController.php'],
        );
    }

    public function testStandAsideWhenBehaviorsMapGainsEntryForAction(): void
    {
        $this->writeContainer([]);

        $this->assertSequence(
            self::results('both'),
            self::results('type'),
            1,
            function (): void {
                $this->writeBehaviors("'Probe\\Target' => ['yii\\behaviors\\AttributeBehavior'],");
            },
            ['project/container.php', 'project/src/BaseController.php', 'project/src/Other.php'],
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
                        externalActionInvalidOptionType: true
                        externalActionUnknownOption: true

            NEON,
        );
        // params keep project stub validation from reporting the plain `Module::$params` array at level max
        $this->writeFile(
            'project/config.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            return [
                'behaviors' => require __DIR__ . '/behaviors.php',
                'container' => require __DIR__ . '/container.php',
                'params' => ['mode' => 'alpha'],
            ];

            PHP,
        );
        $this->writeBehaviors('');
        $this->writeBaseController('');
        $this->writeAction('Target', 'public int $count = 0;', '');
        $this->writeAction('Other', 'public mixed $count = null;', '');
        $this->writeAction('Stable', 'public int $count = 0;', '');

        $controllers = [
            'ProbeController' => "BaseController\n{\n    public function actions(): array\n    {\n"
                . "        return ['run' => ['class' => Target::class, 'colour' => 'red', 'count' => 'all']];\n",
            'StableController' => "\\yii\\base\\Controller\n{\n    public function actions(): array\n    {\n"
                . "        return ['run' => ['class' => Stable::class, 'shade' => 'dark']];\n",
        ];

        foreach ($controllers as $controller => $body) {
            $this->writeFile(
                "project/src/{$controller}.php",
                "<?php\n\ndeclare(strict_types=1);\n\nnamespace Probe;\n\nfinal class {$controller} extends {$body}    }\n}\n",
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
        $stableFiles = [
            'project/phpstan.neon',
            'project/config.php',
            'project/src/ProbeController.php',
            'project/src/Stable.php',
            'project/src/StableController.php',
            ...$stableFiles,
        ];

        $stableInputs = $this->inputHashes($stableFiles);

        $coldStartedAt = time();

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

        $this->assertFirstWarmRunUnchanged(
            $this->analyse(),
            $before,
            $coldStartedAt,
            'Unchanged initial warm run',
        );

        $change();

        $changeStartedAt = time();

        $changed = $this->analyse();

        self::assertRestored(
            $reanalysed,
            $changed['stderr'],
            'Changed warm run',
        );
        self::assertSame(
            $after,
            $changed['results'],
            'Controller must follow the change: report while the definition is proven, stand aside otherwise.',
        );

        $this->assertFirstWarmRunUnchanged(
            $this->analyse(),
            $after,
            $changeStartedAt,
            'Unchanged changed warm run',
        );

        $this->removeTemporaryCacheDirectory();

        $finalCold = $this->analyse();

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
     * Returns the expected results for the rules that report `Probe\ProbeController`.
     *
     * @phpstan-return list<array{string, int, string, string|null}>
     */
    private static function results(string $reported): array
    {
        return [...self::CONTROLLER[$reported] ?? [], ...self::STABLE_CONTROLLER];
    }

    /**
     * Writes the action `Probe\<name>` with one property and the given extra members.
     */
    private function writeAction(string $name, string $property, string $members): void
    {
        $members = $members === '' ? '' : "\n{$members}";

        $this->writeFile(
            "project/src/{$name}.php",
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace Probe;\n\nclass {$name} extends \\yii\\base\\Action\n{\n"
                . "    {$property}\n{$members}}\n",
        );
    }

    /**
     * Writes the parent controller `Probe\BaseController` with the given members.
     */
    private function writeBaseController(string $members): void
    {
        $this->writeFile(
            'project/src/BaseController.php',
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace Probe;\n\nclass BaseController extends \\yii\\base\\Controller\n"
                . "{\n{$members}}\n",
        );
    }

    /**
     * Writes the `behaviors` section with the given entry.
     */
    private function writeBehaviors(string $entry): void
    {
        $this->writeFile(
            'project/behaviors.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n    {$entry}\n];\n",
        );
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
            "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n    'definitions' => [\n        {$definitionLines}\n    ],\n];\n",
        );
    }
}
