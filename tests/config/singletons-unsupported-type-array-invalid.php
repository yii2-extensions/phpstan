<?php

declare(strict_types=1);

return [
    'container' => [
        'singletons' => [
            'unsupported-array-invalid' => ['flag' => 'foo'],
            'service' => ['class' => SplObjectStorage::class],
        ],
    ],
];
