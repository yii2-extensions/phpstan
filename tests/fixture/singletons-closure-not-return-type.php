<?php

declare(strict_types=1);

return [
    'container' => [
        'singletons' => [
            'closure-not-return-type' => static function () {
                return new ArrayObject();
            },
        ],
    ],
];
