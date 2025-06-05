<?php

declare(strict_types=1);

return [
    'container' => [
        'definitions' => [
            'unsupported-type-resource' => fopen('php://memory', 'rb'),
        ],
    ],
];
