<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use yii\base\Widget;
use yii2\extensions\phpstan\tests\support\widget\{
    BeginOverrideWidget,
    BoundWidget,
    ChildWidget,
    OptionsBoundWidget,
    OtherWidget,
    ParamsBoundWidget,
    TargetWidget,
    TargetWidget as ConfiguredWidget,
    WidgetOverrideWidget,
};

/**
 * `widget()` and `begin()` calls whose configuration names an option that the created widget can't assign.
 */
final class WidgetUnknownOption
{
    private const array CONFIG = ['name' => 'constant', 'colour' => 'red'];

    private const string OPTION = 'colour';

    public function aliasedClass(): string
    {
        return ConfiguredWidget::widget(['colour' => 'red']);
    }

    public function baseWidget(): string
    {
        return Widget::widget(['colour' => 'red']);
    }

    public function beginOfWidgetOverride(): object
    {
        return WidgetOverrideWidget::begin(['colour' => 'red']);
    }

    public function boundClassBegin(): object
    {
        return BoundWidget::begin(['name' => 'value']);
    }

    public function boundClassWidget(): string
    {
        return BoundWidget::widget(['name' => 'value']);
    }

    public function caseMismatchedProperty(): string
    {
        return TargetWidget::widget(['Name' => 'value']);
    }

    public function classKeyOverwritten(): string
    {
        return TargetWidget::widget(['class' => OtherWidget::class, 'colour' => 'red']);
    }

    public function constantConfiguration(): string
    {
        return TargetWidget::widget(self::CONFIG);
    }

    public function constantKey(): object
    {
        return TargetWidget::begin([self::OPTION => 'red']);
    }

    public function constructArgumentClassKey(): string
    {
        return TargetWidget::widget(['__construct()' => [['class' => OtherWidget::class]]]);
    }

    public function constructArgumentOption(): object
    {
        return TargetWidget::begin(['__construct()' => [['colour' => 'red']]]);
    }

    public function inheritedClass(): string
    {
        return ChildWidget::widget(['shade' => 'dark', 'colour' => 'red']);
    }

    public function legacyClassKey(): object
    {
        return TargetWidget::begin(['__class' => OtherWidget::class, 'name' => 'value']);
    }

    public function localVariable(): object
    {
        $config = ['colour' => 'red'];

        return TargetWidget::begin($config);
    }

    public function namedConfigBegin(): object
    {
        return TargetWidget::begin(config: ['colour' => 'red']);
    }

    public function namedConfigWidget(): string
    {
        return TargetWidget::widget(config: ['colour' => 'red']);
    }

    public function optionBeforeSetter(): string
    {
        return TargetWidget::widget(['colour' => 'red', 'label' => 'value']);
    }

    public function optionsBoundClass(): string
    {
        return OptionsBoundWidget::widget(['shade' => 'dark', 'colour' => 'red']);
    }

    public function paramsBoundClass(): object
    {
        return ParamsBoundWidget::begin(['colour' => 'red']);
    }

    public function protectedProperty(): string
    {
        return TargetWidget::widget(['internal' => 'value']);
    }

    public function unknownOptionBegin(): object
    {
        return TargetWidget::begin(['colour' => 'red']);
    }

    public function unknownOptionWidget(): string
    {
        return TargetWidget::widget(['name' => 'value', 'colour' => 'red']);
    }

    public function widgetOfBeginOverride(): string
    {
        return BeginOverrideWidget::widget(['colour' => 'red']);
    }
}
