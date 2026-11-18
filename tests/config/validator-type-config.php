<?php

declare(strict_types=1);

use yii\validators\{DateValidator, StringValidator};
use yii2\extensions\phpstan\tests\data\rule\validator\type\{IntegerTypeValidator, TypedStringValidator, TypedValidator};

return [
    'container' => [
        'definitions' => [
            'app\validators\FactoryLimited' => static fn(): TypedValidator => new TypedValidator(),
            'app\validators\Limited' => ['class' => TypedValidator::class],
            DateValidator::class => IntegerTypeValidator::class,
            StringValidator::class => TypedStringValidator::class,
        ],
    ],
];
