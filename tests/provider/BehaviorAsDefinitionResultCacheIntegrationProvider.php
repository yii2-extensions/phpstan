<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\BehaviorAsDefinitionResultCacheIntegrationTest} test cases.
 *
 * @phpstan-type Options list<'bound'|'leaf'|'named'>
 */
final class BehaviorAsDefinitionResultCacheIntegrationProvider
{
    /**
     * Owner members that delegate `attachBehavior()`, which the rule can't prove to reach Yii's attachment.
     */
    private const string ATTACH_OVERRIDE = "class BaseOwner extends \\yii\\base\\Component\n{\n"
        . "    /**\n     * @param array<array-key, mixed>|\\yii\\base\\Behavior|string \$behavior\n     */\n"
        . "    public function attachBehavior(\$name, \$behavior)\n    {\n"
        . "        return parent::attachBehavior(\$name, \$behavior);\n    }\n}";
    /**
     * Owner without overrides.
     */
    private const string PLAIN_OWNER = 'class BaseOwner extends \\yii\\base\\Component {}';

    /**
     * @phpstan-return iterable<string, array{string, string, Options, Options}>
     */
    public static function attachOverrideProvider(): iterable
    {
        yield 'parent owner starts overriding attachBehavior()' => [
            self::PLAIN_OWNER,
            self::ATTACH_OVERRIDE,
            ['bound'],
            [],
        ];
        yield 'parent owner stops overriding attachBehavior()' => [
            self::ATTACH_OVERRIDE,
            self::PLAIN_OWNER,
            [],
            ['bound'],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{string, string, Options, Options}>
     */
    public static function boundClassProvider(): iterable
    {
        yield 'bound class stops extending Behavior' => [
            'class Target extends \\yii\\base\\Behavior {}',
            'class Target extends \\yii\\base\\BaseObject {}',
            [],
            ['bound'],
        ];
        yield 'bound class starts extending Behavior' => [
            'class Target extends \\yii\\base\\BaseObject {}',
            'class Target extends \\yii\\base\\Behavior {}',
            ['bound'],
            [],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{string, string, Options, Options}>
     */
    public static function containerProvider(): iterable
    {
        yield 'bound to a behavior, then to a plain object' => [
            'Probe\\Target',
            'Probe\\Plain',
            [],
            ['bound'],
        ];
        yield 'bound to a plain object, then to a behavior' => [
            'Probe\\Plain',
            'Probe\\Target',
            ['bound'],
            [],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{string, string, Options, Options}>
     */
    public static function namedClassProvider(): iterable
    {
        yield 'named class stops extending Behavior' => [
            'class Named extends \\yii\\base\\Behavior {}',
            'class Named extends \\yii\\base\\BaseObject {}',
            [],
            ['named'],
        ];
        yield 'named class starts extending Behavior' => [
            'class Named extends \\yii\\base\\BaseObject {}',
            'class Named extends \\yii\\base\\Behavior {}',
            ['named'],
            [],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{string, string, Options, Options}>
     */
    public static function parentProvider(): iterable
    {
        yield 'parent stops extending Behavior' => [
            'class Mid extends \\yii\\base\\Behavior {}',
            'class Mid extends \\yii\\base\\BaseObject {}',
            [],
            ['leaf'],
        ];
        yield 'parent starts extending Behavior' => [
            'class Mid extends \\yii\\base\\BaseObject {}',
            'class Mid extends \\yii\\base\\Behavior {}',
            ['leaf'],
            [],
        ];
    }
}
