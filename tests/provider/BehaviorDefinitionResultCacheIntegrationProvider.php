<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\BehaviorDefinitionResultCacheIntegrationTest} test cases.
 */
final class BehaviorDefinitionResultCacheIntegrationProvider
{
    /**
     * @phpstan-return iterable<string, array{string, string, list<'bound'|'duck'|'leaf'>, list<'bound'|'duck'|'leaf'>}>
     */
    public static function attachVisibilityProvider(): iterable
    {
        yield 'public static attach() becomes protected' => [
            'public static function attach(object $owner): void {}',
            'protected static function attach(object $owner): void {}',
            [],
            ['duck'],
        ];
        yield 'protected attach() becomes public static' => [
            'protected function attach(object $owner): void {}',
            'public static function attach(object $owner): void {}',
            ['duck'],
            [],
        ];
    }
    /**
     * @phpstan-return iterable<string, array{string, string, list<'bound'|'duck'|'leaf'>, list<'bound'|'duck'|'leaf'>}>
     */
    public static function boundClassProvider(): iterable
    {
        yield 'bound class stops extending Behavior' => [
            '\\yii\\base\\Behavior',
            '\\yii\\base\\BaseObject',
            [],
            ['bound'],
        ];
        yield 'bound class starts extending Behavior' => [
            '\\yii\\base\\BaseObject',
            '\\yii\\base\\Behavior',
            ['bound'],
            [],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{string, string, list<'bound'|'duck'|'leaf'>, list<'bound'|'duck'|'leaf'>}>
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
     * @phpstan-return iterable<string, array{string, string, list<'bound'|'duck'|'leaf'>, list<'bound'|'duck'|'leaf'>}>
     */
    public static function ensureBehaviorsProvider(): iterable
    {
        yield 'parent owner starts overriding ensureBehaviors()' => [
            '',
            'public function ensureBehaviors(): void {}',
            ['bound'],
            [],
        ];
        yield 'parent owner stops overriding ensureBehaviors()' => [
            'public function ensureBehaviors(): void {}',
            '',
            [],
            ['bound'],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{string, string, list<'bound'|'duck'|'leaf'>, list<'bound'|'duck'|'leaf'>}>
     */
    public static function parentProvider(): iterable
    {
        yield 'parent stops extending Behavior' => [
            '\\yii\\base\\Behavior',
            '\\yii\\base\\BaseObject',
            [],
            ['leaf'],
        ];
        yield 'parent starts extending Behavior' => [
            '\\yii\\base\\BaseObject',
            '\\yii\\base\\Behavior',
            ['leaf'],
            [],
        ];
    }
}
