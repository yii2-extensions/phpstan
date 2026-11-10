<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\rules\CreateObjectInvalidAsDefinitionRule;
use yii2\extensions\phpstan\tests\provider\BehaviorAsDefinitionResultCacheIntegrationProvider;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function dirname;
use function sort;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for the container, class and owner dependencies of {@see CreateObjectInvalidAsDefinitionRule}
 * across result cache runs.
 *
 * {@see BehaviorAsDefinitionResultCacheIntegrationProvider} for test case data providers.
 */
final class BehaviorAsDefinitionResultCacheIntegrationTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Line of each `as` option of `Probe\Factory::create()`.
     */
    private const array PROBE_LINES = ['bound' => 13, 'leaf' => 14, 'named' => 15];

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-behavior-as-definition-result-cache-';

    /**
     * @phpstan-param list<'bound'|'leaf'|'named'> $before
     * @phpstan-param list<'bound'|'leaf'|'named'> $after
     */
    #[DataProviderExternal(BehaviorAsDefinitionResultCacheIntegrationProvider::class, 'boundClassProvider')]
    public function testReanalyseCallWhenClassReachedOnlyThroughContainerChanges(
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
     * @phpstan-param list<'bound'|'leaf'|'named'> $before
     * @phpstan-param list<'bound'|'leaf'|'named'> $after
     */
    #[DataProviderExternal(BehaviorAsDefinitionResultCacheIntegrationProvider::class, 'containerProvider')]
    public function testReanalyseCallWhenContainerBindingChanges(
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
     * @phpstan-param list<'bound'|'leaf'|'named'> $before
     * @phpstan-param list<'bound'|'leaf'|'named'> $after
     */
    #[DataProviderExternal(BehaviorAsDefinitionResultCacheIntegrationProvider::class, 'namedClassProvider')]
    public function testReanalyseCallWhenNamedClassChanges(
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
     * @phpstan-param list<'bound'|'leaf'|'named'> $before
     * @phpstan-param list<'bound'|'leaf'|'named'> $after
     */
    #[DataProviderExternal(BehaviorAsDefinitionResultCacheIntegrationProvider::class, 'parentProvider')]
    public function testReanalyseCallWhenParentInAnotherFileChanges(
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
     * @phpstan-param list<'bound'|'leaf'|'named'> $before
     * @phpstan-param list<'bound'|'leaf'|'named'> $after
     */
    #[DataProviderExternal(BehaviorAsDefinitionResultCacheIntegrationProvider::class, 'attachOverrideProvider')]
    public function testReanalyseCallWhenParentOwnerChangesAttachBehavior(
        string $initial,
        string $changed,
        array $before,
        array $after,
    ): void {
        $this->writeContainer('Probe\\Plain');
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
                        behaviorInvalidAsDefinition: true

            NEON,
        );
        // params keep project stub validation from reporting the plain `Module::$params` array at level max
        $this->writeFile(
            'project/config.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn ['container' => require __DIR__ . '/container.php', "
                . "'params' => ['mode' => 'alpha']];\n",
        );
        $this->writeContainer('Probe\\Target');
        $this->writeClass('Bound', 'class Bound extends \\yii\\base\\Behavior {}');
        $this->writeClass('Target', 'class Target extends \\yii\\base\\Behavior {}');
        $this->writeClass('Plain', 'class Plain extends \\yii\\base\\BaseObject {}');
        $this->writeClass('Mid', 'class Mid extends \\yii\\base\\Behavior {}');
        $this->writeClass('Leaf', 'class Leaf extends Mid {}');
        $this->writeClass('Named', 'class Named extends \\yii\\base\\Behavior {}');
        $this->writeClass('BaseOwner', 'class BaseOwner extends \\yii\\base\\Component {}');
        $this->writeClass('ProbeOwner', 'final class ProbeOwner extends BaseOwner {}');
        $this->writeFactory(
            'Factory',
            "'class' => ProbeOwner::class,\n            'as bound' => Bound::class,\n"
                . "            'as leaf' => Leaf::class,\n            'as named' => 'Probe\\Named',",
        );
        $this->writeFactory(
            'StableFactory',
            "'class' => \\yii\\base\\Component::class,\n            'as plain' => Plain::class,",
        );
    }

    /**
     * Runs the cold, warm, changed, changed warm and final cold analyses around one change, asserting the results of
     * each and that the changed run reanalyses the call.
     *
     * @param list<'bound'|'leaf'|'named'> $before Probe options reported before the change.
     * @param list<'bound'|'leaf'|'named'> $after Probe options reported after the change.
     *
     * @phpstan-param callable(): void $change
     */
    private function assertSequence(array $before, array $after, callable $change): void
    {
        $this->assertChangeSequence(self::results($before), self::results($after), $change, 'call');
    }

    /**
     * Returns the expected results: the reported probe options and the always reported stable option.
     *
     * @param list<'bound'|'leaf'|'named'> $reported Probe options reported.
     *
     * @phpstan-return list<array{string, int, string, null}>
     */
    private static function results(array $reported): array
    {
        $results = [['src/StableFactory.php', 13, 'yii2.behavior.invalidAsDefinition', null]];

        foreach ($reported as $option) {
            $results[] = ['src/Factory.php', self::PROBE_LINES[$option], 'yii2.behavior.invalidAsDefinition', null];
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
     * Writes the container section, binding `Probe\Bound` to the given class.
     */
    private function writeContainer(string $class): void
    {
        $this->writeFile(
            'project/container.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn ['definitions' => ['Probe\\\\Bound' => '{$class}']];\n",
        );
    }

    /**
     * Writes a class whose `create()` returns `Yii::createObject()` of the given configuration items, starting at line
     * 12.
     */
    private function writeFactory(string $name, string $items): void
    {
        $this->writeClass(
            $name,
            "final class {$name}\n{\n    public function create(): object\n    {\n"
                . "        return \\yii\\BaseYii::createObject([\n            {$items}\n        ]);\n    }\n}",
        );
    }
}
