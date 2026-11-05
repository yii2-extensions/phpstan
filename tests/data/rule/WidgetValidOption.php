<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use yii\di\Instance;
use yii2\extensions\phpstan\tests\support\createobject\{ComponentBehavior, TypedValue};
use yii2\extensions\phpstan\tests\support\widget\{
    BoundWidget,
    ChildWidget,
    InitFailingWidget,
    OptionsBoundWidget,
    ParamsBoundWidget,
    RunFailingWidget,
    TargetWidget,
};

/**
 * `widget()` and `begin()` calls whose options the created widget assigns, including calls that fail afterwards.
 */
final class WidgetValidOption
{
    private const array CONFIG = [
        'name' => 'constant',
        'count' => 3,
    ];

    public function boundClassMembers(): string
    {
        return BoundWidget::widget(['colour' => 'red']);
    }

    public function classKeyIgnored(): string
    {
        return TargetWidget::widget(
            [
                'class' => 'Missing\Widget',
                'name' => 'value',
            ],
        );
    }

    public function constantConfiguration(): object
    {
        return TargetWidget::begin(self::CONFIG);
    }

    public function constructArgumentConfiguration(): object
    {
        return TargetWidget::begin(['__construct()' => [['name' => 'value']]]);
    }

    public function constructArgumentReplaced(): string
    {
        return TargetWidget::widget(
            [
                'name' => 'value',
                '__construct()' => [['colour' => 'red']],
            ],
        );
    }

    public function emptyConfiguration(): object
    {
        return TargetWidget::begin([]);
    }

    public function eventAndBehavior(): string
    {
        return TargetWidget::widget(
            [
                'on init' => static function (): void {},
                'as colouring' => ComponentBehavior::class,
                'colour' => 'red',
            ],
        );
    }

    public function inheritedMembers(): object
    {
        return ChildWidget::begin(
            [
                'name' => 'value',
                'shade' => 'dark',
            ],
        );
    }

    public function initFailsAfterAssignment(): object
    {
        return InitFailingWidget::begin(['count' => 1]);
    }

    public function instanceResolved(): string
    {
        return TargetWidget::widget(['value' => Instance::of(TypedValue::class)]);
    }

    public function legacyClassKey(): object
    {
        return TargetWidget::begin(
            [
                '__class' => ChildWidget::class,
                'shade' => 'dark',
            ],
        );
    }

    public function noConfiguration(): string
    {
        return TargetWidget::widget();
    }

    public function optionsBoundClass(): object
    {
        return OptionsBoundWidget::begin(['shade' => 'dark']);
    }

    public function paramsBoundClass(): string
    {
        return ParamsBoundWidget::widget(['name' => 'value']);
    }

    public function propertiesAndSettersBegin(): object
    {
        return TargetWidget::begin(
            [
                'name' => 'value',
                'count' => 1,
                'label' => 'text',
                'id' => 'w0',
            ],
        );
    }

    public function propertiesAndSettersWidget(): string
    {
        return TargetWidget::widget(
            config: [
                'name' => 'value',
                'count' => 1,
                'label' => 'text',
                'id' => 'w0',
            ],
        );
    }

    public function runFailsAfterBegin(): object
    {
        return RunFailingWidget::begin(['name' => 'value']);
    }

    public function runFailsInWidget(): string
    {
        return RunFailingWidget::widget(['name' => 'value']);
    }

    public function weakCoercion(): string
    {
        return TargetWidget::widget(
            [
                'count' => '5',
                'name' => 5,
            ],
        );
    }
}
