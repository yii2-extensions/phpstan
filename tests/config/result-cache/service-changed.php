<?php

declare(strict_types=1);

/** @var array<string, array<string, mixed>> $config */
$config = require __DIR__ . '/base.php';

$config['container'] = [
    'definitions' => [
        'closure' => static fn(): SplStack => new SplStack(),
        'mailer' => ['class' => 'yii\\swiftmailer\\Mailer'],
    ],
    'singletons' => [
        'queue' => 'yii\\queue\\db\\Queue',
    ],
];

return $config;
