<?php

declare(strict_types=1);

return [
    'container' => [
        'definitions' => [
            'alias' => SplStack::class,
            'construct' => ['class' => SplStack::class, '__construct()' => [['flags' => 1]]],
            'constructNamed' => ['class' => SplStack::class, '__construct()' => ['config' => ['flags' => 1]]],
            'constructOtherValue' => ['class' => SplStack::class, '__construct()' => [['mode' => 2]]],
            'factory' => static fn(): SplStack => new SplStack(),
            'moreOptions' => ['class' => SplStack::class, 'flags' => 1, 'mode' => 2],
            'options' => ['class' => SplStack::class, 'flags' => 1],
            'optionsWithParams' => [['class' => SplStack::class, 'flags' => 1], [['mode' => 2]]],
            'otherValue' => ['class' => SplStack::class, 'flags' => 2],
            'params' => [SplStack::class, [['flags' => 1]]],
            'paramsOtherValue' => [SplStack::class, [['mode' => 2]]],
            'paramsTwo' => [SplStack::class, [['flags' => 1], 'second']],
        ],
        'singletons' => [
            'singleton' => SplStack::class,
        ],
    ],
];
