<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\rules\{
    AttachBehaviorUnknownOptionRule,
    BehaviorsUnknownOptionRule,
    CreateObjectBehaviorUnknownOptionRule,
};
use yii2\extensions\phpstan\tests\provider\BehaviorUnknownOptionResultCacheIntegrationProvider;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function dirname;
use function sort;
use function time;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for the class, container and owner dependencies of {@see BehaviorsUnknownOptionRule},
 * {@see AttachBehaviorUnknownOptionRule} and {@see CreateObjectBehaviorUnknownOptionRule} across result cache runs.
 *
 * {@see BehaviorUnknownOptionResultCacheIntegrationProvider} for test case data providers.
 *
 * @phpstan-import-type Rows from BehaviorUnknownOptionResultCacheIntegrationProvider
 */
final class BehaviorUnknownOptionResultCacheIntegrationTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * File and line of each probe configuration, by behavior and channel: `B` for `behaviors()`, `A` for
     * `attachBehaviors()` and `S` for an `as` option of `Yii::createObject()`.
     */
    private const array PROBE_ROWS = [
        'named B' => ['ProbeOwner', 12],
        'leaf B' => ['ProbeOwner', 13],
        'bound B' => ['ProbeOwner', 14],
        'named A' => ['ProbeOwner', 21],
        'leaf A' => ['ProbeOwner', 22],
        'bound A' => ['ProbeOwner', 23],
        'named S' => ['ProbeOwner', 28],
        'leaf S' => ['ProbeOwner', 29],
        'bound S' => ['ProbeOwner', 30],
        'supplied S' => ['SuppliedFactory', 13],
    ];

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-behavior-unknown-option-result-cache-';

    /**
     * @phpstan-param Rows $before
     * @phpstan-param Rows $after
     */
    #[DataProviderExternal(BehaviorUnknownOptionResultCacheIntegrationProvider::class, 'suppliedClassProvider')]
    public function testReanalyseFactoryWhenAsClassNamedOnlyAsStringChanges(
        string $initial,
        string $changed,
        array $before,
        array $after,
    ): void {
        $this->writeClass('Supplied', $initial);

        $this->assertSequence(
            $before,
            $after,
            fn() => $this->writeClass('Supplied', $changed),
        );
    }

    /**
     * @phpstan-param Rows $before
     * @phpstan-param Rows $after
     */
    #[DataProviderExternal(BehaviorUnknownOptionResultCacheIntegrationProvider::class, 'boundClassProvider')]
    public function testReanalyseOwnerWhenClassReachedOnlyThroughContainerChanges(
        string $initial,
        string $changed,
        array $before,
        array $after,
    ): void {
        $this->writeClass('Target', $initial);

        $this->assertSequence(
            $before,
            $after,
            fn() => $this->writeClass('Target', $changed),
        );
    }

    /**
     * @phpstan-param Rows $before
     * @phpstan-param Rows $after
     */
    #[DataProviderExternal(BehaviorUnknownOptionResultCacheIntegrationProvider::class, 'containerProvider')]
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
            fn() => $this->writeContainer($changed),
        );
    }

    /**
     * @phpstan-param Rows $before
     * @phpstan-param Rows $after
     */
    #[DataProviderExternal(BehaviorUnknownOptionResultCacheIntegrationProvider::class, 'namedClassProvider')]
    public function testReanalyseOwnerWhenNamedClassChanges(
        string $initial,
        string $changed,
        array $before,
        array $after,
    ): void {
        $this->writeClass('Named', $initial);

        $this->assertSequence(
            $before,
            $after,
            fn() => $this->writeClass('Named', $changed),
        );
    }

    /**
     * @phpstan-param Rows $before
     * @phpstan-param Rows $after
     */
    #[DataProviderExternal(BehaviorUnknownOptionResultCacheIntegrationProvider::class, 'parentProvider')]
    public function testReanalyseOwnerWhenParentInAnotherFileChanges(
        string $initial,
        string $changed,
        array $before,
        array $after,
    ): void {
        $this->writeClass('Mid', $initial);

        $this->assertSequence(
            $before,
            $after,
            fn() => $this->writeClass('Mid', $changed),
        );
    }

    /**
     * @phpstan-param Rows $before
     * @phpstan-param Rows $after
     */
    #[DataProviderExternal(BehaviorUnknownOptionResultCacheIntegrationProvider::class, 'ensureOverrideProvider')]
    public function testReanalyseOwnerWhenParentOwnerChangesEnsureBehaviors(
        string $initial,
        string $changed,
        array $before,
        array $after,
    ): void {
        $this->writeClass('Named', 'class Named extends \\yii\\base\\Behavior {}');
        $this->writeClass('BaseOwner', $initial);

        $this->assertSequence(
            $before,
            $after,
            fn() => $this->writeClass('BaseOwner', $changed),
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
                        behaviorUnknownOption: true

            NEON,
        );
        // params keep project stub validation from reporting the plain `Module::$params` array at level max
        $this->writeFile(
            'project/config.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn ['container' => require __DIR__ . '/container.php', "
                . "'params' => ['mode' => 'alpha']];\n",
        );
        $this->writeContainer('Probe\\Target');

        $labelled = "extends \\yii\\base\\Behavior\n{\n    public mixed \$label = null;\n}";

        $this->writeClass(
            'Bound',
            'class Bound extends \\yii\\base\\Behavior {}',
        );
        $this->writeClass(
            'Target',
            "class Target {$labelled}",
        );
        $this->writeClass(
            'Unlabelled',
            'class Unlabelled extends \\yii\\base\\Behavior {}',
        );
        $this->writeClass(
            'Mid',
            "class Mid {$labelled}",
        );
        $this->writeClass(
            'Leaf',
            'class Leaf extends Mid {}',
        );
        $this->writeClass(
            'Named',
            "class Named {$labelled}",
        );
        $this->writeClass(
            'BaseOwner',
            'class BaseOwner extends \\yii\\base\\Component {}',
        );
        $this->writeClass(
            'Supplied',
            'class Supplied extends \\yii\\base\\BaseObject {}',
        );
        $this->writeClass(
            'SuppliedTarget',
            'class SuppliedTarget extends \\yii\\base\\Behavior {}',
        );
        // the supplied class is named only as a string, so that only the rule makes the file depend on it
        $this->writeClass(
            'SuppliedFactory',
            "final class SuppliedFactory\n{\n    public function create(): object\n    {\n"
                . "        return \\yii\\BaseYii::createObject([\n"
                . "            'class' => \\yii\\base\\Component::class,\n"
                . "            'as supplied' => ['class' => 'Probe\\\\Supplied', 'label' => 1],\n"
                . "        ]);\n    }\n}",
        );
        $this->writeClass(
            'ProbeOwner',
            <<<'PHP'
            final class ProbeOwner extends BaseOwner
            {
                public function behaviors(): array
                {
                    return [
                        'named' => ['class' => Named::class, 'label' => 1],
                        'leaf' => ['class' => Leaf::class, 'label' => 1],
                        'bound' => ['class' => Bound::class, 'label' => 1],
                    ];
                }

                public function register(): object
                {
                    $this->attachBehaviors([
                        'named' => ['class' => Named::class, 'label' => 1],
                        'leaf' => ['class' => Leaf::class, 'label' => 1],
                        'bound' => ['class' => Bound::class, 'label' => 1],
                    ]);

                    return \yii\BaseYii::createObject([
                        'class' => \yii\base\Component::class,
                        'as named' => ['class' => Named::class, 'label' => 1],
                        'as leaf' => ['class' => Leaf::class, 'label' => 1],
                        'as bound' => ['class' => Bound::class, 'label' => 1],
                    ]);
                }
            }
            PHP,
        );
        $this->writeClass(
            'StableOwner',
            "final class StableOwner extends \\yii\\base\\Component\n{\n    public function behaviors(): array\n"
                . "    {\n        return ['stable' => ['class' => \\yii\\base\\Behavior::class, 'missing' => 1]];\n"
                . "    }\n}",
        );
    }

    /**
     * Runs the cold, warm, changed, changed warm and final cold analyses around one change, asserting the results of
     * each and that the changed run reanalyses the owner.
     *
     * @param list<string> $before Probe rows reported before the change.
     * @param list<string> $after Probe rows reported after the change.
     *
     * @phpstan-param Rows $before
     * @phpstan-param Rows $after
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
     * Returns the expected results: the reported probe rows and the always reported stable entry.
     *
     * @param list<string> $reported Probe rows reported.
     *
     * @phpstan-param Rows $reported
     * @phpstan-return list<array{string, int, string, null}>
     */
    private static function results(array $reported): array
    {
        $results = [['src/StableOwner.php', 11, 'yii2.behavior.unknownOption', null]];

        foreach ($reported as $row) {
            [$file, $line] = self::PROBE_ROWS[$row];

            $results[] = ["src/{$file}.php", $line, 'yii2.behavior.unknownOption', null];
        }

        sort($results);

        return $results;
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
     * Writes the container section, binding `Probe\Bound` to the given class and `Probe\Supplied` to
     * `Probe\SuppliedTarget`.
     */
    private function writeContainer(string $class): void
    {
        $this->writeFile(
            'project/container.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn ['definitions' => ['Probe\\\\Bound' => '{$class}', "
                . "'Probe\\\\Supplied' => 'Probe\\\\SuppliedTarget']];\n",
        );
    }
}
