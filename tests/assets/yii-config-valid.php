<?php

use yii\phpstan\tests\Yii\MyActiveRecord;

return [
    'components' => [
        'customComponent' => [
            'class' => MyActiveRecord::class,
        ],
        'customInitializedComponent' => new MyActiveRecord(),
    ],
    'container' => [
        'singletons' => [
            'singleton-string' => MyActiveRecord::class,
            'singleton-closure' => function(): \SplStack {
                return new \SplStack();
            },
            'singleton-service' => ['class' => \SplObjectStorage::class],
            'singleton-nested-service-class' => [
                ['class' => \SplFileInfo::class]
            ]
        ],
        'definitions' => [
            'closure' => function(): \SplStack {
                return new \SplStack();
            },
            'service' => ['class' => \SplObjectStorage::class],
            'nested-service-class' => [
                ['class' => \SplFileInfo::class]
            ],
            MyActiveRecord::class => [
                'flag' => 'foo',
            ],
        ]
    ]
];
