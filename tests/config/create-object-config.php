<?php

declare(strict_types=1);

use yii2\extensions\phpstan\tests\support\createobject\{
    AliasTarget,
    ComponentBehavior,
    ComponentBoundTarget,
    ComponentMappedContract,
    ComponentMappedTarget,
    EmptyDefinitionTarget,
    FactoryTarget,
    InstanceTarget,
    SingletonTarget,
    Target,
    UnresolvedTarget,
};

return [
    'behaviors' => [
        ComponentMappedContract::class => [ComponentBehavior::class],
        ComponentMappedTarget::class => [ComponentBehavior::class],
    ],
    'container' => [
        'definitions' => [
            AliasTarget::class => Target::class,
            ComponentBoundTarget::class => static fn(): ComponentBoundTarget => new ComponentBoundTarget(),
            EmptyDefinitionTarget::class => [],
            FactoryTarget::class => static fn(): FactoryTarget => new FactoryTarget(),
            InstanceTarget::class => new InstanceTarget(),
            UnresolvedTarget::class => static fn() => new UnresolvedTarget(),
        ],
        'singletons' => [
            SingletonTarget::class => [],
        ],
    ],
];
