<?php

declare(strict_types=1);

return [
    'container' => [
        'definitions' => [
            'closure-not-return-type' => static function () {
                return new ArrayObject();
            },
        ],
    ],
];
