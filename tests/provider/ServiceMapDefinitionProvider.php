<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use ArrayObject;
use SplStack;
use yii\caching\{ArrayCache, CacheInterface, DummyCache, FileCache};
use yii\di\Instance;
use yii\web\View;
use yii2\extensions\phpstan\tests\support\stub\{InvokableViewFactory, MyActiveRecord, PlainService};

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\ServiceMapDefinitionTest} test cases.
 *
 * Provides component and service IDs paired with the class each definition form must resolve to, or `null`.
 */
final class ServiceMapDefinitionProvider
{
    /**
     * @return iterable<string, array{string, string|null}>
     */
    public static function componentProvider(): iterable
    {
        yield 'array callable' => ['arrayCallable', null];
        yield 'array with __class' => ['arrayDunderClass', View::class];
        yield 'array with class and __class' => ['arrayClassAndDunderClass', View::class];
        yield 'array with class and leading backslash' => ['arrayClassLeadingBackslash', View::class];
        yield 'array with class naming aliased container ID' => ['arrayClassNamingAliasedId', FileCache::class];
        yield 'array with class naming unresolvable container ID' => ['containerArrayAliasUnresolvable', null];
        yield 'array with class' => ['arrayClass', View::class];
        yield 'array without class' => ['arrayWithoutClass', null];
        yield 'closure returning aliased container ID' => ['closureReturningAliasedId', CacheInterface::class];
        yield 'closure returning builtin' => ['closureBuiltin', null];
        yield 'closure returning class' => ['closureClass', View::class];
        yield 'closure returning intersection' => ['closureIntersection', null];
        yield 'closure returning nullable class' => ['closureNullable', null];
        yield 'closure returning union' => ['closureUnion', null];
        yield 'closure without return type' => ['closureUntyped', null];
        yield 'Instance reference' => ['instance', Instance::class];
        yield 'invokable object' => ['invokableObject', InvokableViewFactory::class];
        yield 'null' => ['null', null];
        yield 'object of aliased container ID class' => ['objectOfAliasedId', ArrayCache::class];
        yield 'object' => ['object', View::class];
        yield 'string naming aliased container ID' => ['stringNamingAliasedId', FileCache::class];
        yield 'string naming container ID on cycle' => ['containerCycle', null];
        yield 'string naming container ID' => ['containerAlias', View::class];
        yield 'string naming unresolvable container ID' => ['containerAliasUnresolvable', null];
        yield 'string with leading backslash' => ['stringLeadingBackslash', View::class];
        yield 'string' => ['string', View::class];
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function componentWithoutClassProvider(): iterable
    {
        yield 'absent ID' => ['absent', false];
        yield 'array callable' => ['arrayCallable', false];
        yield 'array with __class' => ['arrayDunderClass', false];
        yield 'array with class' => ['arrayClass', false];
        yield 'array without class' => ['arrayWithoutClass', true];
        yield 'closure without return type' => ['closureUntyped', false];
        yield 'null' => ['null', false];
    }

    /**
     * @return iterable<string, array{string, string|null}>
     */
    public static function configOrderServiceProvider(): iterable
    {
        yield 'definition class overriding singleton' => ['definitionClassOverride', ArrayCache::class];
        yield 'definition class overriding unresolvable singleton' => ['definitionResolvedOverride', FileCache::class];
        yield 'definition closure without return type overriding singleton' => ['definitionClosureOverride', null];
    }

    /**
     * @return iterable<string, array{string, string|null}>
     */
    public static function serviceProvider(): iterable
    {
        yield 'array callable' => [
            'arrayCallable',
            null,
        ];
        yield 'array with __class' => [
            'arrayDunderClass',
            SplStack::class,
        ];
        yield 'array with class and __class' => [
            'arrayClassAndDunderClass',
            SplStack::class,
        ];
        yield 'array with class and leading backslash' => [
            'arrayClassLeadingBackslash',
            SplStack::class,
        ];
        yield 'array with class naming aliased container ID' => [
            'arrayClassNamingAliasedId',
            FileCache::class,
        ];
        yield 'array with class' => [
            'arrayClass',
            SplStack::class,
        ];
        yield 'array without class under BaseObject class ID' => [
            MyActiveRecord::class,
            MyActiveRecord::class,
        ];
        yield 'array without class under abstract class ID' => [
            'yii\caching\Cache',
            null,
        ];
        yield 'array without class under interface ID' => [
            'yii\base\Configurable',
            null,
        ];
        yield 'array without class under missing class ID' => [
            'app\missing\Service',
            null,
        ];
        yield 'array without class under non-BaseObject class ID' => [
            PlainService::class,
            PlainService::class,
        ];
        yield 'array without class' => [
            'arrayWithoutClass',
            null,
        ];
        yield 'closure returning aliased container ID' => [
            'closureReturningAliasedId',
            CacheInterface::class,
        ];
        yield 'closure returning builtin' => [
            'closureBuiltin',
            null,
        ];
        yield 'closure returning class' => [
            'closureClass',
            SplStack::class,
        ];
        yield 'closure returning intersection' => [
            'closureIntersection',
            null,
        ];
        yield 'closure returning nullable class' => [
            'closureNullable',
            null,
        ];
        yield 'closure returning union' => [
            'closureUnion',
            null,
        ];
        yield 'closure without return type' => [
            'closureUntyped',
            null,
        ];
        yield 'container ID aliasing class' => [
            ArrayCache::class,
            DummyCache::class,
        ];
        yield 'container ID aliasing interface' => [
            CacheInterface::class,
            FileCache::class,
        ];
        yield 'container ID array class naming unresolvable ID' => [
            'unresolvable.arrayAlias',
            null,
        ];
        yield 'container ID array class' => [
            'mailer.arrayAlias',
            View::class,
        ];
        yield 'container ID string chain ending in unresolvable ID' => [
            'unresolvable.chain',
            null,
        ];
        yield 'container ID string chain' => [
            'mailer.chain',
            View::class,
        ];
        yield 'container ID string cycle end' => [
            'cycle.b',
            null,
        ];
        yield 'container ID string cycle start' => [
            'cycle.a',
            null,
        ];
        yield 'container ID string naming closure-defined ID' => [
            'closureReturningAliasedId.alias',
            CacheInterface::class,
        ];
        yield 'container ID string naming empty definition ID' => [
            'emptyDefinitionAlias',
            'SplObjectStorage',
        ];
        yield 'container ID string naming non-BaseObject class ID' => [
            'plainService.alias',
            PlainService::class,
        ];
        yield 'container ID string naming ID overridden by unresolvable singleton' => [
            'singletonClosureOverride.alias',
            null,
        ];
        yield 'container ID string naming unresolvable ID' => [
            'unresolvable.alias',
            null,
        ];
        yield 'container ID string' => [
            'mailer.alias',
            View::class,
        ];
        yield 'empty array under BaseObject class ID' => [
            View::class,
            View::class,
        ];
        yield 'empty array under non-BaseObject class ID' => [
            'SplObjectStorage',
            'SplObjectStorage',
        ];
        yield 'Instance reference naming aliased container ID' => [
            'instanceNamingAliasedId',
            View::class,
        ];
        yield 'Instance reference naming unresolvable container ID' => [
            'instanceNamingUnresolvableId',
            null,
        ];
        yield 'Instance reference' => [
            'instance',
            SplStack::class,
        ];
        yield 'invokable object' => [
            'invokableObject',
            View::class,
        ];
        yield 'list holding a single array definition' => [
            'nestedList',
            null,
        ];
        yield 'null under non-class ID' => [
            'null',
            null,
        ];
        yield 'object of aliased container ID class' => [
            'objectOfAliasedId',
            ArrayCache::class,
        ];
        yield 'object' => [
            'object',
            SplStack::class,
        ];
        yield 'singleton closure returning class' => [
            'singletonClosureClass',
            SplStack::class,
        ];
        yield 'singleton closure without return type overriding definition' => [
            'singletonClosureOverride',
            null,
        ];
        yield 'singleton closure without return type' => [
            'singletonClosureUntyped',
            null,
        ];
        yield 'singleton container ID string from definitions' => [
            'singletonAlias',
            View::class,
        ];
        yield 'singleton empty array overriding definition under non-class ID' => [
            'singletonEmptyOverride',
            null,
        ];
        yield 'singleton Instance reference overriding definition' => [
            'singletonInstanceOverride',
            DummyCache::class,
        ];
        yield 'singleton Instance reference' => [
            'singletonInstance',
            SplStack::class,
        ];
        yield 'singleton string overriding object definition' => [
            'singletonOverride',
            FileCache::class,
        ];
        yield 'singleton string overriding unresolvable definition' => [
            'singletonResolvedOverride',
            FileCache::class,
        ];
        yield 'singleton string with leading backslash' => [
            'singletonString',
            SplStack::class,
        ];
        yield 'singleton wrapped array with params' => [
            'singletonWrappedArrayWithParams',
            SplStack::class,
        ];
        yield 'string naming aliased container ID' => [
            'stringNamingAliasedId',
            FileCache::class,
        ];
        yield 'string with leading backslash' => [
            'stringLeadingBackslash',
            SplStack::class,
        ];
        yield 'string' => [
            'string',
            SplStack::class,
        ];
        yield 'wrapped array with params' => [
            'wrappedArrayWithParams',
            SplStack::class,
        ];
        yield 'wrapped closure returning aliased container ID' => [
            'wrappedClosureReturningAliasedId',
            CacheInterface::class,
        ];
        yield 'wrapped string with params' => [
            'wrappedStringWithParams',
            SplStack::class,
        ];
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function unresolvedServiceProvider(): iterable
    {
        yield 'absent ID' => ['absent', false];
        yield 'array without class under abstract class ID' => ['yii\caching\Cache', true];
        yield 'array without class under non-BaseObject class ID' => [PlainService::class, false];
        yield 'class-keyed closure without return type' => [ArrayObject::class, true];
        yield 'closure without return type' => ['closureUntyped', true];
        yield 'container ID on cycle' => ['cycle.a', true];
        yield 'container ID string naming unresolvable ID' => ['unresolvable.alias', true];
        yield 'Instance reference naming unresolvable container ID' => ['instanceNamingUnresolvableId', true];
        yield 'Instance reference' => ['instance', false];
        yield 'empty array under non-BaseObject class ID' => ['SplObjectStorage', false];
        yield 'list holding a single array definition' => ['nestedList', true];
        yield 'null under non-class ID' => ['null', false];
        yield 'string' => ['string', false];
    }
}
