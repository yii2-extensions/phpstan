<?php

declare(strict_types=1);

return [
    'container' => [
        'definitions' => [
            'alias' => SplStack::class,
            'factory' => static fn(): SplStack => new SplStack(),
            'moreOptions' => ['class' => SplStack::class, 'flags' => 1, 'mode' => 2],
            'options' => ['class' => SplStack::class, 'flags' => 1],
            'otherValue' => ['class' => SplStack::class, 'flags' => 2],
        ],
        'singletons' => [
            'singleton' => SplStack::class,
        ],
    ],
];
