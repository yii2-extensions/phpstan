<?php

declare(strict_types=1);

return [
    'container' => [
        'singletons' => [
            'closure-not-return-type' => static fn() => new ArrayObject(),
        ],
    ],
];
