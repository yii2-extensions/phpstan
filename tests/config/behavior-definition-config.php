<?php

declare(strict_types=1);

use yii\di\Instance;
use yii2\extensions\phpstan\tests\support\behavior\{
    AbstractBoundBehavior,
    AbstractPlainObject,
    AttachableBehavior,
    BehaviorContract,
    BoundToBehaviorObject,
    ComponentBoundBehavior,
    CycleBehavior,
    FactoryBoundBehavior,
    InterfaceBoundBehavior,
    InvokableBoundBehavior,
    InvokableFactory,
    MappedAwayBehavior,
    NotAttachableObject,
    NotBehaviorComponent,
    OptionBehavior,
    OptionDefaultsBehavior,
    OptionRedirectedBehavior,
    OptionTargetBehavior,
    OtherBehavior,
    PlainClass,
    RedirectedBehavior,
    SingletonBoundBehavior,
    UnboundContract,
};

return [
    'container' => [
        'definitions' => [
            'aliasA' => 'aliasB',
            'aliasB' => AttachableBehavior::class,
            'attachableBehaviorId' => AttachableBehavior::class,
            'cycleBehaviorAlias' => CycleBehavior::class,
            'factoryId' => static fn(): NotAttachableObject => new NotAttachableObject(),
            'instanceAlias' => Instance::of('attachableBehaviorId'),
            'notAttachableAlias' => 'notAttachableTarget',
            'notAttachableTarget' => PlainClass::class,
            'optionBehaviorId' => OptionBehavior::class,
            AbstractBoundBehavior::class => AbstractPlainObject::class,
            BehaviorContract::class => AttachableBehavior::class,
            BoundToBehaviorObject::class => AttachableBehavior::class,
            ComponentBoundBehavior::class => NotBehaviorComponent::class,
            CycleBehavior::class => 'cycleBehaviorAlias',
            FactoryBoundBehavior::class => static fn(): NotAttachableObject => new NotAttachableObject(),
            InterfaceBoundBehavior::class => UnboundContract::class,
            InvokableBoundBehavior::class => InvokableFactory::class,
            MappedAwayBehavior::class => NotAttachableObject::class,
            OptionDefaultsBehavior::class => ['class' => OptionDefaultsBehavior::class, 'preset' => 'container'],
            OptionRedirectedBehavior::class => OptionTargetBehavior::class,
            RedirectedBehavior::class => OtherBehavior::class,
        ],
        'singletons' => [
            'singletonId' => NotAttachableObject::class,
            SingletonBoundBehavior::class => NotAttachableObject::class,
        ],
    ],
];
