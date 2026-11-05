<?php

declare(strict_types=1);

use yii2\extensions\phpstan\tests\support\createobject\ComponentBehavior;
use yii2\extensions\phpstan\tests\support\widget\{
    BoundWidget,
    ChildWidget,
    FactoryWidget,
    MappedWidget,
    ObjectBoundWidget,
    OptionsBoundWidget,
    OtherWidget,
    ParamsBoundWidget,
    PlainObject,
    SetterBoundWidget,
    SingletonWidget,
    TargetWidget,
};

return [
    'behaviors' => [
        MappedWidget::class => [ComponentBehavior::class],
    ],
    'container' => [
        'definitions' => [
            BoundWidget::class => OtherWidget::class,
            FactoryWidget::class => static fn(): FactoryWidget => new FactoryWidget(),
            ObjectBoundWidget::class => PlainObject::class,
            OptionsBoundWidget::class => ['class' => ChildWidget::class, 'name' => 'definition'],
            ParamsBoundWidget::class => [['class' => TargetWidget::class], [['name' => 'param']]],
            SetterBoundWidget::class => ['class' => TargetWidget::class, 'label' => 'definition'],
        ],
        'singletons' => [
            SingletonWidget::class => [],
        ],
    ],
];
