<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use function sprintf;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\BehaviorUnknownOptionResultCacheIntegrationTest} test cases.
 *
 * @phpstan-type Row 'bound A'|'bound B'|'bound S'|'leaf A'|'leaf B'|'leaf S'|'named A'|'named B'|'named S'|'supplied S'
 * @phpstan-type Rows list<Row>
 */
final class BehaviorUnknownOptionResultCacheIntegrationProvider
{
    /**
     * Behavior class `Probe\<name>` with the `label` property.
     */
    private const string LABELLED = "class %s extends \\yii\\base\\Behavior\n{\n    public mixed \$label = null;\n}";
    /**
     * Behavior class `Probe\<name>` without the `label` property.
     */
    private const string UNLABELLED = 'class %s extends \\yii\\base\\Behavior {}';

    /**
     * @phpstan-return iterable<string, array{string, string, Rows, Rows}>
     */
    public static function boundClassProvider(): iterable
    {
        yield 'class reached only through the container loses the property' => [
            sprintf(self::LABELLED, 'Target'),
            sprintf(self::UNLABELLED, 'Target'),
            [],
            ['bound B', 'bound A', 'bound S'],
        ];
        yield 'class reached only through the container gains the property' => [
            sprintf(self::UNLABELLED, 'Target'),
            sprintf(self::LABELLED, 'Target'),
            ['bound B', 'bound A', 'bound S'],
            [],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{string, string, Rows, Rows}>
     */
    public static function containerProvider(): iterable
    {
        yield 'bound to a behavior with the property, then to one without' => [
            'Probe\\Target',
            'Probe\\Unlabelled',
            [],
            ['bound B', 'bound A', 'bound S'],
        ];
        yield 'bound to a behavior without the property, then to one with' => [
            'Probe\\Unlabelled',
            'Probe\\Target',
            ['bound B', 'bound A', 'bound S'],
            [],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{string, string, Rows, Rows}>
     */
    public static function ensureOverrideProvider(): iterable
    {
        $override = "class BaseOwner extends \\yii\\base\\Component\n{\n    public function ensureBehaviors(): void\n"
            . "    {\n        parent::ensureBehaviors();\n    }\n}";
        $plain = 'class BaseOwner extends \\yii\\base\\Component {}';

        yield 'parent owner starts overriding ensureBehaviors()' => [
            $plain,
            $override,
            ['named B', 'named A', 'named S'],
            ['named A', 'named S'],
        ];
        yield 'parent owner stops overriding ensureBehaviors()' => [
            $override,
            $plain,
            ['named A', 'named S'],
            ['named B', 'named A', 'named S'],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{string, string, Rows, Rows}>
     */
    public static function namedClassProvider(): iterable
    {
        yield 'named class loses the property' => [
            sprintf(self::LABELLED, 'Named'),
            sprintf(self::UNLABELLED, 'Named'),
            [],
            ['named B', 'named A', 'named S'],
        ];
        yield 'named class gains the property' => [
            sprintf(self::UNLABELLED, 'Named'),
            sprintf(self::LABELLED, 'Named'),
            ['named B', 'named A', 'named S'],
            [],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{string, string, Rows, Rows}>
     */
    public static function parentProvider(): iterable
    {
        yield 'parent in another file loses the property' => [
            sprintf(self::LABELLED, 'Mid'),
            sprintf(self::UNLABELLED, 'Mid'),
            [],
            ['leaf B', 'leaf A', 'leaf S'],
        ];
        yield 'parent in another file gains the property' => [
            sprintf(self::UNLABELLED, 'Mid'),
            sprintf(self::LABELLED, 'Mid'),
            ['leaf B', 'leaf A', 'leaf S'],
            [],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{string, string, Rows, Rows}>
     */
    public static function suppliedClassProvider(): iterable
    {
        // only `Component::__set()` reads the supplied class; the container creates `Probe\SuppliedTarget`
        yield 'class named only as a string stops extending Behavior' => [
            'class Supplied extends \\yii\\base\\Behavior {}',
            'class Supplied extends \\yii\\base\\BaseObject {}',
            ['supplied S'],
            [],
        ];
        yield 'class named only as a string starts extending Behavior' => [
            'class Supplied extends \\yii\\base\\BaseObject {}',
            'class Supplied extends \\yii\\base\\Behavior {}',
            [],
            ['supplied S'],
        ];
    }
}
