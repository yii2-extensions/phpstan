<?php

declare(strict_types=1);

return [
    'container' => [
        'definitions' => [
            'unsupported-array-invalid' => ['flag' => 'foo'],
            'service' => ['class' => SplObjectStorage::class],
        ],
    ],
];
