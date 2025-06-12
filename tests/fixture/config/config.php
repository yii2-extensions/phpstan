<?php

declare(strict_types=1);

use yii2\extensions\phpstan\tests\stub\MyActiveRecord;
use yii2\extensions\phpstan\tests\stub\User;

return [
    'components' => [
        'assetManager' => [
            'basePath' => '@runtime/assets',
        ],
        'customComponent' => [
            'class' => MyActiveRecord::class,
        ],
        'customInitializedComponent' => new MyActiveRecord(),
        'user' => [
            'class' => 'yii\web\User',
            'identityClass' => User::class,
        ],
    ],
    'container' => [
        'singletons' => [
            'singleton-string' => MyActiveRecord::class,
            'singleton-closure' => static function (): SplStack {
                return new SplStack();
            },
            'singleton-service' => [
                'class' => SplObjectStorage::class,
            ],
            'singleton-nested-service-class' => [
                [
                    'class' => SplFileInfo::class,
                ],
            ],
        ],
        'definitions' => [
            'closure' => static function (): SplStack {
                return new SplStack();
            },
            'service' => [
                'class' => SplObjectStorage::class,
            ],
            'nested-service-class' => [
                [
                    'class' => SplFileInfo::class,
                ],
            ],
            MyActiveRecord::class => [
                'flag' => 'foo',
            ],
        ],
    ],
];
