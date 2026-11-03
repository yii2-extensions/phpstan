<?php

declare(strict_types=1);

use yii\di\Instance;

return [
    'container' => [
        'definitions' => [
            ArrayIterator::class => ArrayIterator::class,
            ArrayObject::class => null,
            SplStack::class => [],
            'Probe\Bypassed' => static fn(): SplQueue => new SplQueue(),
            'alias.array' => ['class' => SplStack::class],
            'alias.backslash' => '\\' . SplStack::class,
            'alias.bypass' => '\Probe\Bypassed',
            'alias.first' => 'alias.second',
            'alias.instance' => Instance::of(SplStack::class),
            'alias.legacy' => ['__class' => SplStack::class],
            'alias.noBypass' => 'Probe\Bypassed',
            'alias.second' => Instance::of('alias.third'),
            'alias.string' => SplStack::class,
            'alias.third' => ['class' => SplStack::class],
            'alias.wrapped' => [SplStack::class, []],
            'alias.wrappedArray' => [['class' => SplStack::class], []],
            'cycle.a' => 'cycle.b',
            'cycle.b' => 'cycle.c',
            'cycle.c' => 'cycle.b',
            'cycle.first' => 'cycle.second',
            'cycle.second' => 'cycle.first',
            'opaque.callable' => [SplStack::class, 'count'],
            'opaque.classes' => ['class' => SplStack::class, '__class' => SplStack::class],
            'opaque.classInstance' => ['class' => Instance::of(SplStack::class)],
            'opaque.classNull' => ['class' => null],
            'opaque.emptyString' => '',
            'opaque.factory' => static fn(): SplStack => new SplStack(),
            'opaque.list' => [SplStack::class],
            'opaque.object' => new SplStack(),
            'opaque.options' => ['class' => SplStack::class, 'flags' => 1],
            'opaque.params' => [SplStack::class, ['first']],
            'opaque.toFactory' => 'opaque.factory',
            'opaque.toSingleton' => 'singleton.plain',
            'opaque.zero' => '0',
            'replaced' => SplStack::class,
        ],
        'singletons' => [
            'replaced' => SplStack::class,
            'singleton.empty' => [],
            'singleton.plain' => SplStack::class,
        ],
    ],
];
