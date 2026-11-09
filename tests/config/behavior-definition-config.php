<?php

declare(strict_types=1);

use yii\di\Instance;
use yii2\extensions\phpstan\tests\support\behavior\{
    AttachableBehavior,
    BehaviorContract,
    MappedAwayBehavior,
    NotAttachableObject,
    OtherBehavior,
    PlainClass,
    RedirectedBehavior,
};

return [
    'container' => [
        'definitions' => [
            'aliasA' => 'aliasB',
            'aliasB' => AttachableBehavior::class,
            'attachableBehaviorId' => AttachableBehavior::class,
            'factoryId' => static fn(): NotAttachableObject => new NotAttachableObject(),
            'instanceAlias' => Instance::of('attachableBehaviorId'),
            'notAttachableAlias' => 'notAttachableTarget',
            'notAttachableTarget' => PlainClass::class,
            BehaviorContract::class => AttachableBehavior::class,
            MappedAwayBehavior::class => NotAttachableObject::class,
            RedirectedBehavior::class => OtherBehavior::class,
        ],
        'singletons' => [
            'singletonId' => NotAttachableObject::class,
        ],
    ],
];
