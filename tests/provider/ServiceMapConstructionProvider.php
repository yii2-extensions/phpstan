<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\ServiceMapConstructionTest} test cases.
 */
final class ServiceMapConstructionProvider
{
    /**
     * @phpstan-return iterable<string, array{string, string|null}>
     */
    public static function constructionProvider(): iterable
    {
        yield 'unbound class' => ['Unbound\Target', 'Unbound\Target'];
        yield 'unbound class with leading backslash' => ['\Unbound\Target', 'Unbound\Target'];
        yield 'unbound empty ID' => ['', null];
        yield 'empty array' => ['SplStack', 'SplStack'];
        yield 'null' => ['ArrayObject', 'ArrayObject'];
        yield 'class string naming the ID' => ['ArrayIterator', 'ArrayIterator'];
        yield 'class string' => ['alias.string', 'SplStack'];
        yield 'Instance' => ['alias.instance', 'SplStack'];
        yield 'array with class only' => ['alias.array', 'SplStack'];
        yield 'array with __class only' => ['alias.legacy', 'SplStack'];
        yield 'pair with empty params' => ['alias.wrapped', 'SplStack'];
        yield 'pair of array with empty params' => ['alias.wrappedArray', 'SplStack'];
        yield 'three hops' => ['alias.first', 'SplStack'];
        yield 'leading backslash target' => ['alias.backslash', 'SplStack'];
        yield 'leading backslash target misses a factory' => ['alias.bypass', 'Probe\Bypassed'];
        yield 'target bound to a factory' => ['alias.noBypass', null];
        yield 'factory' => ['opaque.factory', null];
        yield 'array callable' => ['opaque.callable', null];
        yield 'object' => ['opaque.object', null];
        yield 'array with options' => ['opaque.options', null];
        yield 'array with class and __class' => ['opaque.classes', null];
        yield 'array with Instance class' => ['opaque.classInstance', null];
        yield 'array with null class' => ['opaque.classNull', null];
        yield 'one-element list' => ['opaque.list', null];
        yield 'pair with params' => ['opaque.params', null];
        yield 'empty string' => ['opaque.emptyString', null];
        yield 'zero string' => ['opaque.zero', null];
        yield 'alias to factory' => ['opaque.toFactory', null];
        yield 'alias to singleton' => ['opaque.toSingleton', null];
        yield 'singleton' => ['singleton.plain', null];
        yield 'empty singleton' => ['singleton.empty', null];
        yield 'definition replaced by singleton' => ['replaced', null];
        yield 'two-ID cycle' => ['cycle.first', null];
        yield 'cycle after one hop' => ['cycle.a', null];
    }
}
