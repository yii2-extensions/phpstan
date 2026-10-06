<?php

declare(strict_types=1);

return [
    'components' => [
        'typed' => static fn(): SplStack => new SplStack(),
        'untyped' => static fn() => new SplStack(),
    ],
];
