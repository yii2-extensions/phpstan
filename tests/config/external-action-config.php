<?php

declare(strict_types=1);

use yii2\extensions\phpstan\tests\support\action\{
    BoundAction,
    ChildAction,
    ConstructBoundAction,
    FactoryAction,
    MappedAction,
    OptionsBoundAction,
    OtherAction,
    ParamsBoundAction,
    SetterBoundAction,
    SingletonAction,
    TargetAction,
};
use yii2\extensions\phpstan\tests\support\createobject\ComponentBehavior;

return [
    'behaviors' => [
        MappedAction::class => [ComponentBehavior::class],
    ],
    'container' => [
        'definitions' => [
            'action.bound' => OtherAction::class,
            BoundAction::class => OtherAction::class,
            ConstructBoundAction::class => ['class' => TargetAction::class, '__construct()' => ['bound']],
            FactoryAction::class => static fn(): FactoryAction => new FactoryAction('factory', null),
            OptionsBoundAction::class => ['class' => ChildAction::class, 'name' => 'definition'],
            ParamsBoundAction::class => [['class' => TargetAction::class], ['bound']],
            SetterBoundAction::class => ['class' => TargetAction::class, 'label' => 'definition'],
        ],
        'singletons' => [
            SingletonAction::class => [],
        ],
    ],
];
