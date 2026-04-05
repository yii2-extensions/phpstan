<?php

declare(strict_types=1);

return [
    'params' => [
        'turnstile.siteKey' => '',
        'adminEmail' => 'admin@example.com',
        'maxItems' => 100,
        'debugMode' => true,
        'ratio' => 1.5,
        'nullableParam' => null,
        'nested' => [
            'key1' => 'value1',
            'key2' => 42,
        ],
        'tags' => [
            'php',
            'yii2',
        ],
    ],
];
