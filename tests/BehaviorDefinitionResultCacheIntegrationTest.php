<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\rules\BehaviorsInvalidDefinitionRule;
use yii2\extensions\phpstan\tests\provider\BehaviorDefinitionResultCacheIntegrationProvider;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function dirname;
use function sort;
use function time;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for the container and class dependencies of {@see BehaviorsInvalidDefinitionRule} across result
 * cache runs.
 *
 * {@see BehaviorDefinitionResultCacheIntegrationProvider} for test case data providers.
 */
final class BehaviorDefinitionResultCacheIntegrationTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Line of each entry of `Probe\ProbeOwner::behaviors()`.
     */
    private const array PROBE_LINES = ['bound' => 15, 'leaf' => 16, 'duck' => 17];

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-behavior-definition-result-cache-';

    /**
     * @phpstan-param list<'bound'|'duck'|'leaf'> $before
     * @phpstan-param list<'bound'|'duck'|'leaf'> $after
     */
    #[DataProviderExternal(BehaviorDefinitionResultCacheIntegrationProvider::class, 'boundClassProvider')]
    public function testReanalyseOwnerWhenClassReachedOnlyThroughContainerChanges(
        string $initial,
        string $changed,
        array $before,
        array $after,
    ): void {
        $this->writeClass(
            'Target',
            "class Target extends {$initial} {}",
        );

        $this->assertSequence(
            $before,
            $after,
            function () use ($changed): void {
                $this->writeClass(
                    'Target',
                    "class Target extends {$changed} {}",
                );
            },
        );
    }

    /**
     * @phpstan-param list<'bound'|'duck'|'leaf'> $before
     * @phpstan-param list<'bound'|'duck'|'leaf'> $after
     */
    #[DataProviderExternal(BehaviorDefinitionResultCacheIntegrationProvider::class, 'containerProvider')]
    public function testReanalyseOwnerWhenContainerBindingChanges(
        string $initial,
        string $changed,
        array $before,
        array $after,
    ): void {
        $this->writeContainer($initial);

        $this->assertSequence(
            $before,
            $after,
            function () use ($changed): void {
                $this->writeContainer($changed);
            },
        );
    }

    /**
     * @phpstan-param list<'bound'|'duck'|'leaf'> $before
     * @phpstan-param list<'bound'|'duck'|'leaf'> $after
     */
    #[DataProviderExternal(BehaviorDefinitionResultCacheIntegrationProvider::class, 'parentProvider')]
    public function testReanalyseOwnerWhenParentInAnotherFileChanges(
        string $initial,
        string $changed,
        array $before,
        array $after,
    ): void {
        $this->writeMid($initial);

        $this->assertSequence(
            $before,
            $after,
            function () use ($changed): void {
                $this->writeMid($changed);
            },
        );
    }

    /**
     * @phpstan-param list<'bound'|'duck'|'leaf'> $before
     * @phpstan-param list<'bound'|'duck'|'leaf'> $after
     */
    #[DataProviderExternal(BehaviorDefinitionResultCacheIntegrationProvider::class, 'ensureBehaviorsProvider')]
    public function testReanalyseOwnerWhenParentOwnerChangesEnsureBehaviors(
        string $initial,
        string $changed,
        array $before,
        array $after,
    ): void {
        $this->writeContainer('Probe\\Plain');
        $this->writeBaseOwner($initial);

        $this->assertSequence(
            $before,
            $after,
            function () use ($changed): void {
                $this->writeBaseOwner($changed);
            },
        );
    }

    /**
     * @phpstan-param list<'bound'|'duck'|'leaf'> $before
     * @phpstan-param list<'bound'|'duck'|'leaf'> $after
     */
    #[DataProviderExternal(BehaviorDefinitionResultCacheIntegrationProvider::class, 'attachVisibilityProvider')]
    public function testReanalyseOwnerWhenResolvedClassChangesAttach(
        string $initial,
        string $changed,
        array $before,
        array $after,
    ): void {
        $this->writeDuck($initial);

        $this->assertSequence(
            $before,
            $after,
            function () use ($changed): void {
                $this->writeDuck($changed);
            },
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
                        behaviorInvalidDefinition: true

            NEON,
        );
        // params keep project stub validation from reporting the plain `Module::$params` array at level max
        $this->writeFile(
            'project/config.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn ['container' => require __DIR__ . '/container.php', "
                . "'params' => ['mode' => 'alpha']];\n",
        );
        $this->writeContainer('Probe\\Target');
        $this->writeMid('\\yii\\base\\Behavior');
        $this->writeDuck('public static function attach(object $owner): void {}');
        $this->writeClass(
            'Target',
            'class Target extends \\yii\\base\\Behavior {}',
        );
        $this->writeClass(
            'Plain',
            'class Plain extends \\yii\\base\\BaseObject {}',
        );
        $this->writeClass(
            'Leaf',
            'class Leaf extends Mid {}',
        );
        $this->writeBaseOwner('');
        $this->writeOwner(
            'ProbeOwner',
            'BaseOwner',
            "'bound' => 'boundId',\n            'leaf' => Leaf::class,\n            'duck' => Duck::class,",
        );
        $this->writeOwner(
            'StableOwner',
            '\\yii\\base\\Component',
            "'plain' => Plain::class,",
        );
    }

    /**
     * Runs the cold, warm, changed, changed warm and final cold analyses around one change, asserting the results of
     * each and that the changed run reanalyses the owner.
     *
     * @param list<'bound'|'duck'|'leaf'> $before Probe entries reported before the change.
     * @param list<'bound'|'duck'|'leaf'> $after Probe entries reported after the change.
     *
     * @phpstan-param callable(): void $change
     */
    private function assertSequence(array $before, array $after, callable $change): void
    {
        $expectedBefore = self::results($before);
        $expectedAfter = self::results($after);

        $coldStartedAt = time();

        $initialCold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $initialCold['stderr'],
            'Initial run must start without a result cache.',
        );
        self::assertSame(
            $expectedBefore,
            $initialCold['results'],
            'Initial run must report the initial results.',
        );

        $this->assertFirstWarmRunUnchanged(
            $this->analyse(),
            $expectedBefore,
            $coldStartedAt,
            'Unchanged initial warm run',
        );

        $change();

        $changeStartedAt = time();

        $changed = $this->analyse();

        self::assertGreaterThanOrEqual(
            1,
            self::reanalysedFiles($changed['stderr'], 'Changed warm run'),
            'Changed warm run must reanalyse the owner.',
        );
        self::assertSame(
            $expectedAfter,
            $changed['results'],
            'Owner must follow the change.',
        );

        $this->assertFirstWarmRunUnchanged(
            $this->analyse(),
            $expectedAfter,
            $changeStartedAt,
            'Unchanged changed warm run',
        );

        $this->removeTemporaryCacheDirectory();

        self::assertSame(
            $expectedAfter,
            $this->analyse()['results'],
            'Final cold results must match the results after detecting the change.',
        );
    }

    /**
     * Returns the expected results: the reported probe entries and the always reported stable entry.
     *
     * @param list<'bound'|'duck'|'leaf'> $reported Probe entries reported.
     *
     * @phpstan-return list<array{string, int, string, null}>
     */
    private static function results(array $reported): array
    {
        $results = [['src/StableOwner.php', 15, 'yii2.behavior.invalidDefinition', null]];

        foreach ($reported as $entry) {
            $results[] = ['src/ProbeOwner.php', self::PROBE_LINES[$entry], 'yii2.behavior.invalidDefinition', null];
        }

        sort($results);

        return $results;
    }

    /**
     * Writes `Probe\BaseOwner`, the component that `Probe\ProbeOwner` extends, with the given members.
     */
    private function writeBaseOwner(string $members): void
    {
        $this->writeClass(
            'BaseOwner',
            "class BaseOwner extends \\yii\\base\\Component\n{\n    {$members}\n}",
        );
    }

    /**
     * Writes the class `Probe\<name>` with the given declaration.
     */
    private function writeClass(string $name, string $declaration): void
    {
        $this->writeFile(
            "project/src/{$name}.php",
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace Probe;\n\n{$declaration}\n",
        );
    }

    /**
     * Writes the container section, binding `boundId` to the given class.
     */
    private function writeContainer(string $class): void
    {
        $this->writeFile(
            'project/container.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn ['definitions' => ['boundId' => '{$class}']];\n",
        );
    }

    /**
     * Writes `Probe\Duck`, a `BaseObject` subclass with the given `attach()` declaration.
     */
    private function writeDuck(string $attach): void
    {
        $this->writeClass(
            'Duck',
            "class Duck extends \\yii\\base\\BaseObject\n{\n    {$attach}\n}",
        );
    }

    /**
     * Writes `Probe\Mid`, the parent of `Probe\Leaf`, extending the given class.
     */
    private function writeMid(string $parent): void
    {
        $this->writeClass(
            'Mid',
            "class Mid extends {$parent} {}",
        );
    }

    /**
     * Writes a component extending the given class whose `behaviors()` returns the given entries, starting at line 15.
     */
    private function writeOwner(string $name, string $parent, string $entries): void
    {
        $this->writeClass(
            $name,
            "final class {$name} extends {$parent}\n{\n    /**\n     * @return array<array-key, mixed>\n"
                . "     */\n    public function behaviors(): array\n    {\n        return [\n            {$entries}\n"
                . "        ];\n    }\n}",
        );
    }
}
