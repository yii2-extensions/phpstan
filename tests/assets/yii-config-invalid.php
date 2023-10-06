<?php

declare(strict_types=1);

return [
    'container' => ['singletons' => [
        'no-return-type' => function () {
            return new \ArrayObject();
        },
    ]],
];
