<?php

declare(strict_types=1);

use yii\validators\InlineValidator;
use yii2\extensions\phpstan\tests\data\rule\validator\type\TypedMethodValidator;

return [
    'container' => [
        'definitions' => [
            InlineValidator::class => TypedMethodValidator::class,
        ],
    ],
];
