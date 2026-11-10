<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\rules\{
    AttachBehaviorInvalidOptionTypeRule,
    AttachBehaviorUnknownOptionRule,
    BehaviorsInvalidOptionTypeRule,
    BehaviorsUnknownOptionRule,
    CreateObjectBehaviorInvalidOptionTypeRule,
    CreateObjectBehaviorUnknownOptionRule,
};
use yii2\extensions\phpstan\tests\provider\BehaviorOptionResultCacheIntegrationProvider;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function dirname;
use function sort;
use function sprintf;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for the class, container and owner dependencies of {@see BehaviorsUnknownOptionRule},
 * {@see AttachBehaviorUnknownOptionRule}, {@see CreateObjectBehaviorUnknownOptionRule},
 * {@see BehaviorsInvalidOptionTypeRule}, {@see AttachBehaviorInvalidOptionTypeRule} and
 * {@see CreateObjectBehaviorInvalidOptionTypeRule} across result cache runs.
 *
 * {@see BehaviorOptionResultCacheIntegrationProvider} for test case data providers.
 *
 * @phpstan-import-type Rows from BehaviorOptionResultCacheIntegrationProvider
 * @phpstan-import-type Rule from BehaviorOptionResultCacheIntegrationProvider
 */
final class BehaviorOptionResultCacheIntegrationTest extends TestCase
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
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-behavior-option-result-cache-';

    /**
     * Error identifier of the rule switch enabled by {@see writeProject()}.
     */
    private string $identifier = '';

    /**
     * @phpstan-param Rule $rule
     * @phpstan-param Rows $before
     * @phpstan-param Rows $after
     */
    #[DataProviderExternal(BehaviorOptionResultCacheIntegrationProvider::class, 'suppliedClassProvider')]
    public function testReanalyseFactoryWhenAsClassNamedOnlyAsStringChanges(
        string $rule,
        string $initial,
        string $changed,
        array $before,
        array $after,
    ): void {
        $this->writeProject($rule);
        $this->writeClass(
            'Supplied',
            $initial,
        );

        $this->assertSequence(
            $before,
            $after,
            fn() => $this->writeClass('Supplied', $changed),
        );
    }

    /**
     * @phpstan-param Rule $rule
     * @phpstan-param Rows $before
     * @phpstan-param Rows $after
     */
    #[DataProviderExternal(BehaviorOptionResultCacheIntegrationProvider::class, 'boundClassProvider')]
    public function testReanalyseOwnerWhenClassReachedOnlyThroughContainerChanges(
        string $rule,
        string $initial,
        string $changed,
        array $before,
        array $after,
    ): void {
        $this->writeProject($rule);
        $this->writeClass(
            'Target',
            $initial,
        );

        $this->assertSequence(
            $before,
            $after,
            fn() => $this->writeClass('Target', $changed),
        );
    }

    /**
     * @phpstan-param Rule $rule
     * @phpstan-param Rows $before
     * @phpstan-param Rows $after
     */
    #[DataProviderExternal(BehaviorOptionResultCacheIntegrationProvider::class, 'containerProvider')]
    public function testReanalyseOwnerWhenContainerBindingChanges(
        string $rule,
        string $initial,
        string $changed,
        array $before,
        array $after,
    ): void {
        $this->writeProject($rule);
        $this->writeContainer($initial);

        $this->assertSequence(
            $before,
            $after,
            fn() => $this->writeContainer($changed),
        );
    }

    /**
     * @phpstan-param Rule $rule
     * @phpstan-param Rows $before
     * @phpstan-param Rows $after
     */
    #[DataProviderExternal(BehaviorOptionResultCacheIntegrationProvider::class, 'namedClassProvider')]
    public function testReanalyseOwnerWhenNamedClassChanges(
        string $rule,
        string $initial,
        string $changed,
        array $before,
        array $after,
    ): void {
        $this->writeProject($rule);
        $this->writeClass(
            'Named',
            $initial,
        );

        $this->assertSequence(
            $before,
            $after,
            fn() => $this->writeClass('Named', $changed),
        );
    }

    /**
     * @phpstan-param Rule $rule
     * @phpstan-param Rows $before
     * @phpstan-param Rows $after
     */
    #[DataProviderExternal(BehaviorOptionResultCacheIntegrationProvider::class, 'parentProvider')]
    public function testReanalyseOwnerWhenParentInAnotherFileChanges(
        string $rule,
        string $initial,
        string $changed,
        array $before,
        array $after,
    ): void {
        $this->writeProject($rule);
        $this->writeClass(
            'Mid',
            $initial,
        );

        $this->assertSequence(
            $before,
            $after,
            fn() => $this->writeClass('Mid', $changed),
        );
    }

    /**
     * @phpstan-param Rule $rule
     * @phpstan-param Rows $before
     * @phpstan-param Rows $after
     */
    #[DataProviderExternal(BehaviorOptionResultCacheIntegrationProvider::class, 'ensureOverrideProvider')]
    public function testReanalyseOwnerWhenParentOwnerChangesEnsureBehaviors(
        string $rule,
        string $initial,
        string $changed,
        array $before,
        array $after,
    ): void {
        $this->writeProject($rule);
        $this->writeClass(
            'Named',
            sprintf(BehaviorOptionResultCacheIntegrationProvider::REJECTING[$rule], 'Named'),
        );
        $this->writeClass(
            'BaseOwner',
            $initial,
        );

        $this->assertSequence(
            $before,
            $after,
            fn() => $this->writeClass('BaseOwner', $changed),
        );
    }

    protected function setUp(): void
    {
        $this->createWorkDirectory();

        // params keep project stub validation from reporting the plain `Module::$params` array at level max
        $this->writeFile(
            'project/config.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn ['container' => require __DIR__ . '/container.php', "
                . "'params' => ['mode' => 'alpha']];\n",
        );
        $this->writeContainer('Probe\\Target');

        $this->writeClass(
            'Bound',
            'class Bound extends \\yii\\base\\Behavior {}',
        );
        $this->writeClass(
            'Target',
            sprintf(BehaviorOptionResultCacheIntegrationProvider::ACCEPTING, 'Target'),
        );
        $this->writeClass(
            'Mid',
            sprintf(BehaviorOptionResultCacheIntegrationProvider::ACCEPTING, 'Mid'),
        );
        $this->writeClass(
            'Leaf',
            'class Leaf extends Mid {}',
        );
        $this->writeClass(
            'Named',
            sprintf(BehaviorOptionResultCacheIntegrationProvider::ACCEPTING, 'Named'),
        );
        $this->writeClass(
            'BaseOwner',
            'class BaseOwner extends \\yii\\base\\Component {}',
        );
        $this->writeClass(
            'Supplied',
            'class Supplied extends \\yii\\base\\BaseObject {}',
        );
        // the supplied class is named only as a string, so that only the rule makes the file depend on it
        $this->writeClass(
            'SuppliedFactory',
            "final class SuppliedFactory\n{\n    public function create(): object\n    {\n"
                . "        return \\yii\\BaseYii::createObject([\n"
                . "            'class' => \\yii\\base\\Component::class,\n"
                . "            'as supplied' => ['class' => 'Probe\\\\Supplied', 'label' => 'all'],\n"
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
                        'named' => ['class' => Named::class, 'label' => 'all'],
                        'leaf' => ['class' => Leaf::class, 'label' => 'all'],
                        'bound' => ['class' => Bound::class, 'label' => 'all'],
                    ];
                }

                public function register(): object
                {
                    $this->attachBehaviors([
                        'named' => ['class' => Named::class, 'label' => 'all'],
                        'leaf' => ['class' => Leaf::class, 'label' => 'all'],
                        'bound' => ['class' => Bound::class, 'label' => 'all'],
                    ]);

                    return \yii\BaseYii::createObject([
                        'class' => \yii\base\Component::class,
                        'as named' => ['class' => Named::class, 'label' => 'all'],
                        'as leaf' => ['class' => Leaf::class, 'label' => 'all'],
                        'as bound' => ['class' => Bound::class, 'label' => 'all'],
                    ]);
                }
            }
            PHP,
        );
        // `Probe\Stable` rejects the value under each rule, so that every run reports a diagnostic
        $this->writeClass(
            'StableOwner',
            "final class StableOwner extends \\yii\\base\\Component\n{\n    public function behaviors(): array\n"
                . "    {\n        return ['stable' => ['class' => Stable::class, 'label' => 'all']];\n"
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
        $this->assertChangeSequence(
            $this->results($before),
            $this->results($after),
            $change,
            'owner',
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
    private function results(array $reported): array
    {
        $results = [['src/StableOwner.php', 11, $this->identifier, null]];

        foreach ($reported as $row) {
            [$file, $line] = self::PROBE_ROWS[$row];

            $results[] = ["src/{$file}.php", $line, $this->identifier, null];
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

    /**
     * Writes the analysis configuration enabling only the rule switch, and the classes that reject the probe value
     * under that rule.
     *
     * @phpstan-param Rule $rule
     */
    private function writeProject(string $rule): void
    {
        $this->identifier = BehaviorOptionResultCacheIntegrationProvider::IDENTIFIERS[$rule];

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
                        {$rule}: true

            NEON,
        );

        foreach (['Rejecting', 'Stable', 'SuppliedTarget'] as $name) {
            $this->writeClass($name, sprintf(BehaviorOptionResultCacheIntegrationProvider::REJECTING[$rule], $name));
        }
    }
}
