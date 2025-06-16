<?php

declare(strict_types=1);

use yii\base\View;
use yii\console\Application;

return [
    'phpstan' => [
        'application_type' => Application::class,
    ],
    'components' => [
        'view' => [
            'class' => View::class,
        ],
    ],
];
