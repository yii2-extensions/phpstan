<?php

declare(strict_types=1);

return [
    'container' => [
        'definitions' => [
            'alias' => SplStack::class,
            'factory' => static fn(): SplStack => new SplStack(),
        ],
        'singletons' => [
            'singleton' => SplStack::class,
        ],
    ],
];
