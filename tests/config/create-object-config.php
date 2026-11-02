<?php

declare(strict_types=1);

use yii2\extensions\phpstan\tests\support\createobject\{
    AliasTarget,
    EmptyDefinitionTarget,
    FactoryTarget,
    InstanceTarget,
    SingletonTarget,
    Target,
    UnresolvedTarget,
};

return [
    'container' => [
        'definitions' => [
            AliasTarget::class => Target::class,
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
