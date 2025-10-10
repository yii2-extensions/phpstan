<?php

declare(strict_types=1);

use yii\web\View;
use yii2\extensions\phpstan\tests\support\stub\{
    BehaviorOne,
    BehaviorTwo,
    ModelWithConflictingProperty,
    ModelWithMultipleBehaviors,
    MyActiveRecord,
    MyComponent,
    NestedSetsBehavior,
    NestedSetsModel,
    SlugBehavior,
    User,
};

return [
    'behaviors' => [
        ModelWithConflictingProperty::class => [
            NestedSetsBehavior::class,
        ],
        ModelWithMultipleBehaviors::class => [
            NestedSetsBehavior::class,
            SlugBehavior::class,
        ],
        NestedSetsModel::class => [
            NestedSetsBehavior::class,
        ],
        MyComponent::class => [
            BehaviorOne::class,
            BehaviorTwo::class,
        ],
        'OtherComponent' => [
            'BehaviorThree',
        ],
    ],
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
        'view' => [
            'class' => View::class,
        ],
    ],
    'container' => [
        'definitions' => [
            'closure' => static function (): SplStack {
                return new SplStack();
            },
            MyActiveRecord::class => [
                'flag' => 'foo',
            ],
            'nested-service-class' => [
                [
                    'class' => SplFileInfo::class,
                ],
            ],
            'service' => [
                'class' => SplObjectStorage::class,
            ],
        ],
        'singletons' => [
            'singleton-closure' => static function (): SplStack {
                return new SplStack();
            },
            'singleton-nested-service-class' => [
                [
                    'class' => SplFileInfo::class,
                ],
            ],
            'singleton-service' => [
                'class' => SplObjectStorage::class,
            ],
            'singleton-string' => MyActiveRecord::class,
        ],
    ],
];
