<?php

declare(strict_types=1);

use yii\di\Instance;
use yii2\extensions\phpstan\tests\support\createobject\{
    AbstractTarget,
    AliasTarget,
    BindingContract,
    ComponentBehavior,
    ComponentBoundTarget,
    ComponentMappedContract,
    ComponentMappedTarget,
    ComponentMembersTarget,
    ConfiguringFactory,
    CustomConstructorTarget,
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
            BindingContract::class => Target::class,
            ComponentBoundTarget::class => static fn(): ComponentBoundTarget => new ComponentBoundTarget(),
            EmptyDefinitionTarget::class => [],
            FactoryTarget::class => static fn(): FactoryTarget => new FactoryTarget(),
            InstanceTarget::class => new InstanceTarget(),
            UnresolvedTarget::class => static fn() => new UnresolvedTarget(),
            'binding.abstract' => AbstractTarget::class,
            'binding.array' => ['class' => Target::class],
            'binding.backslash' => '\\' . FactoryTarget::class,
            'binding.classes' => ['class' => Target::class, '__class' => Target::class],
            'binding.component' => ComponentMembersTarget::class,
            'binding.configuredFactory' => ConfiguringFactory::create(...),
            'binding.customConstructor' => CustomConstructorTarget::class,
            'binding.cycleFirst' => 'binding.cycleSecond',
            'binding.cycleSecond' => 'binding.cycleFirst',
            'binding.factory' => static fn(): Target => new Target(),
            'binding.first' => Instance::of('binding.second'),
            'binding.instance' => Instance::of(Target::class),
            'binding.legacy' => ['__class' => Target::class],
            'binding.mapped' => ComponentMappedTarget::class,
            'binding.missing' => 'Missing\Target',
            'binding.object' => new Target(),
            'binding.options' => ['class' => Target::class, 'name' => 'definition'],
            'binding.params' => [Target::class, [[], 'second']],
            'binding.paramsHop' => 'binding.params',
            'binding.replaced' => Target::class,
            'binding.second' => ['__class' => 'binding.third'],
            'binding.third' => Target::class,
            'binding.toFactory' => FactoryTarget::class,
            'binding.toSingleton' => SingletonTarget::class,
            'binding.wrapped' => [Target::class, []],
            'binding.zero' => '0',
        ],
        'singletons' => [
            SingletonTarget::class => [],
            'binding.replaced' => Target::class,
            'binding.singleton' => Target::class,
        ],
    ],
];
