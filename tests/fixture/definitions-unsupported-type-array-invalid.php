<?php

declare(strict_types=1);

use function fopen;

return [
    'container' => [
        'definitions' => [
            'unsupported-type-resource' => fopen('php://memory', 'rb'),
        ],
    ],
];
