<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use yii2\extensions\phpstan\tests\support\widget\{BoundWidget, ChildWidget, OtherWidget, TargetWidget};

/**
 * `widget()` and `begin()` calls whose option values the created widget never accepts.
 */
final class WidgetInvalidOptionType
{
    private const array CONFIG = ['name' => 'constant', 'count' => 'all'];

    public function arrayForStringProperty(): object
    {
        return TargetWidget::begin(['name' => []]);
    }

    public function arrayForStringSetter(): string
    {
        return TargetWidget::widget(['label' => []]);
    }

    public function boundClass(): object
    {
        return BoundWidget::begin(['colour' => []]);
    }

    public function constantConfiguration(): string
    {
        return TargetWidget::widget(self::CONFIG);
    }

    public function constructArgument(): string
    {
        return TargetWidget::widget(['__construct()' => [['count' => 'all']]]);
    }

    public function inheritedProperty(): string
    {
        return ChildWidget::widget(['count' => 'all']);
    }

    public function legacyClassKey(): string
    {
        return TargetWidget::widget(['__class' => OtherWidget::class, 'colour' => []]);
    }

    public function namedConfig(): object
    {
        return TargetWidget::begin(config: ['count' => []]);
    }

    public function nonNumericStringForIntProperty(): string
    {
        return TargetWidget::widget(['count' => 'all']);
    }

    public function objectForClassProperty(): object
    {
        return TargetWidget::begin(['value' => new OtherWidget()]);
    }
}
