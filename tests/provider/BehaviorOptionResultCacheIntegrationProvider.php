<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use function array_keys;
use function sprintf;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\BehaviorOptionResultCacheIntegrationTest} test cases.
 *
 * Each scenario runs once per behavior option rule switch, with the declarations that make that rule report the probe
 * value `'label' => 'all'` or accept it.
 *
 * @phpstan-type Row 'bound A'|'bound B'|'bound S'|'leaf A'|'leaf B'|'leaf S'|'named A'|'named B'|'named S'|'supplied S'
 * @phpstan-type Rows list<Row>
 * @phpstan-type Rule 'behaviorInvalidOptionType'|'behaviorUnknownOption'
 */
final class BehaviorOptionResultCacheIntegrationProvider
{
    /**
     * Behavior class `Probe\<name>` whose `label` property accepts the probe value.
     */
    public const string ACCEPTING = "class %s extends \\yii\\base\\Behavior\n{\n    public mixed \$label = null;\n}";
    /**
     * Error identifier reported by each rule switch.
     */
    public const array IDENTIFIERS = [
        'behaviorInvalidOptionType' => 'yii2.behavior.invalidOptionType',
        'behaviorUnknownOption' => 'yii2.behavior.unknownOption',
    ];
    /**
     * Behavior class `Probe\<name>` that makes each rule switch report the probe value: with an `int` `label` property,
     * or without it.
     */
    public const array REJECTING = [
        'behaviorInvalidOptionType' => "class %s extends \\yii\\base\\Behavior\n{\n    public int \$label = 0;\n}",
        'behaviorUnknownOption' => 'class %s extends \\yii\\base\\Behavior {}',
    ];

    /**
     * @phpstan-return iterable<string, array{Rule, string, string, Rows, Rows}>
     */
    public static function boundClassProvider(): iterable
    {
        foreach (self::REJECTING as $rule => $rejecting) {
            yield "{$rule}: class reached only through the container starts rejecting" => [
                $rule,
                sprintf(self::ACCEPTING, 'Target'),
                sprintf($rejecting, 'Target'),
                [],
                ['bound B', 'bound A', 'bound S'],
            ];
            yield "{$rule}: class reached only through the container stops rejecting" => [
                $rule,
                sprintf($rejecting, 'Target'),
                sprintf(self::ACCEPTING, 'Target'),
                ['bound B', 'bound A', 'bound S'],
                [],
            ];
        }
    }

    /**
     * @phpstan-return iterable<string, array{Rule, string, string, Rows, Rows}>
     */
    public static function containerProvider(): iterable
    {
        foreach (array_keys(self::IDENTIFIERS) as $rule) {
            yield "{$rule}: bound to an accepting behavior, then to a rejecting one" => [
                $rule,
                'Probe\\Target',
                'Probe\\Rejecting',
                [],
                ['bound B', 'bound A', 'bound S'],
            ];
            yield "{$rule}: bound to a rejecting behavior, then to an accepting one" => [
                $rule,
                'Probe\\Rejecting',
                'Probe\\Target',
                ['bound B', 'bound A', 'bound S'],
                [],
            ];
        }
    }

    /**
     * @phpstan-return iterable<string, array{Rule, string, string, Rows, Rows}>
     */
    public static function ensureOverrideProvider(): iterable
    {
        $override = "class BaseOwner extends \\yii\\base\\Component\n{\n    public function ensureBehaviors(): void\n"
            . "    {\n        parent::ensureBehaviors();\n    }\n}";
        $plain = 'class BaseOwner extends \\yii\\base\\Component {}';

        foreach (array_keys(self::IDENTIFIERS) as $rule) {
            yield "{$rule}: parent owner starts overriding ensureBehaviors()" => [
                $rule,
                $plain,
                $override,
                ['named B', 'named A', 'named S'],
                ['named A', 'named S'],
            ];
            yield "{$rule}: parent owner stops overriding ensureBehaviors()" => [
                $rule,
                $override,
                $plain,
                ['named A', 'named S'],
                ['named B', 'named A', 'named S'],
            ];
        }
    }

    /**
     * @phpstan-return iterable<string, array{Rule, string, string, Rows, Rows}>
     */
    public static function namedClassProvider(): iterable
    {
        foreach (self::REJECTING as $rule => $rejecting) {
            yield "{$rule}: named class starts rejecting" => [
                $rule,
                sprintf(self::ACCEPTING, 'Named'),
                sprintf($rejecting, 'Named'),
                [],
                ['named B', 'named A', 'named S'],
            ];
            yield "{$rule}: named class stops rejecting" => [
                $rule,
                sprintf($rejecting, 'Named'),
                sprintf(self::ACCEPTING, 'Named'),
                ['named B', 'named A', 'named S'],
                [],
            ];
        }
    }

    /**
     * @phpstan-return iterable<string, array{Rule, string, string, Rows, Rows}>
     */
    public static function parentProvider(): iterable
    {
        foreach (self::REJECTING as $rule => $rejecting) {
            yield "{$rule}: parent in another file starts rejecting" => [
                $rule,
                sprintf(self::ACCEPTING, 'Mid'),
                sprintf($rejecting, 'Mid'),
                [],
                ['leaf B', 'leaf A', 'leaf S'],
            ];
            yield "{$rule}: parent in another file stops rejecting" => [
                $rule,
                sprintf($rejecting, 'Mid'),
                sprintf(self::ACCEPTING, 'Mid'),
                ['leaf B', 'leaf A', 'leaf S'],
                [],
            ];
        }
    }

    /**
     * @phpstan-return iterable<string, array{Rule, string, string, Rows, Rows}>
     */
    public static function suppliedClassProvider(): iterable
    {
        // only `Component::__set()` reads the supplied class; the container creates the rejecting `Probe\SuppliedTarget`
        foreach (array_keys(self::IDENTIFIERS) as $rule) {
            yield "{$rule}: class named only as a string stops extending Behavior" => [
                $rule,
                'class Supplied extends \\yii\\base\\Behavior {}',
                'class Supplied extends \\yii\\base\\BaseObject {}',
                ['supplied S'],
                [],
            ];
            yield "{$rule}: class named only as a string starts extending Behavior" => [
                $rule,
                'class Supplied extends \\yii\\base\\BaseObject {}',
                'class Supplied extends \\yii\\base\\Behavior {}',
                [],
                ['supplied S'],
            ];
        }
    }
}
