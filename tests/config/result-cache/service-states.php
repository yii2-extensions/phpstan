<?php

declare(strict_types=1);

return [
    'container' => [
        'definitions' => [
            'typed' => SplStack::class,
            'untyped' => static fn() => new SplStack(),
        ],
    ],
];
