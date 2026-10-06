<?php

declare(strict_types=1);

return [
    'phpstan' => [
        'application_type' => 'yii\web\Application',
    ],
    'behaviors' => [
        'app\models\Post' => ['app\behaviors\SlugBehavior', 'app\behaviors\TimestampBehavior'],
        'app\models\User' => ['app\behaviors\TimestampBehavior'],
    ],
    'components' => [
        'cache' => ['class' => 'yii\caching\FileCache'],
        'initialized' => new SplObjectStorage(),
        'user' => ['class' => 'yii\web\User', 'identityClass' => 'app\models\User'],
    ],
    'container' => [
        'definitions' => [
            'closure' => static fn(): SplStack => new SplStack(),
            'mailer' => ['class' => 'yii\symfonymailer\Mailer'],
        ],
        'singletons' => [
            'queue' => 'yii\queue\file\Queue',
        ],
    ],
    'params' => [
        'adminEmail' => 'admin@example.com',
        'callback' => static fn(): int => 1,
        'maxItems' => 100,
    ],
];
