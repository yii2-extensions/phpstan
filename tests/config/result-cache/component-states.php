<?php

declare(strict_types=1);

return [
    'components' => [
        'classless' => [],
        'classlessConfigured' => ['cookieValidationKey' => 'analysis-only'],
        'typed' => static fn(): SplStack => new SplStack(),
        'untyped' => static fn() => new SplStack(),
    ],
];
