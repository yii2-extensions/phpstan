<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use yii\base\Widget;
use yii\di\Instance;
use yii2\extensions\phpstan\tests\support\createobject\{ComponentBehavior, TypedValue};
use yii2\extensions\phpstan\tests\support\widget\{
    AbstractWidget,
    BeginOverrideWidget,
    BehaviorsWidget,
    ChildWidget,
    CustomConstructorWidget,
    FactoryWidget,
    MappedWidget,
    ObjectBoundWidget,
    OtherWidget,
    PlainObject,
    SetterBoundWidget,
    SingletonWidget,
    TargetWidget,
    TraitOverrideWidget,
    WidgetOverrideWidget,
};

/**
 * `widget()` and `begin()` calls whose called class, method, arguments, configuration or created widget the rules can't
 * prove, written in a widget so that scope-relative calls create it.
 */
final class WidgetStandAside extends Widget
{
    private string $method = 'widget';

    private bool $toggle = true;

    public function abstractClass(): string
    {
        return AbstractWidget::widget(['name' => 'value']);
    }

    public function beginOverride(): object
    {
        return BeginOverrideWidget::begin(['colour' => 'red']);
    }

    public function behaviorAfterOption(): string
    {
        return TargetWidget::widget(
            [
                'colour' => 'red',
                'as colouring' => ComponentBehavior::class,
            ],
        );
    }

    public function behaviorsOverride(): string
    {
        return BehaviorsWidget::widget(['colour' => 'red']);
    }

    public function customConstructor(): string
    {
        return CustomConstructorWidget::widget(['colour' => 'red']);
    }

    public function documentedConfiguration(): string
    {
        return $this->documented(['colour' => 'red']);
    }

    public function dynamicClass(): string
    {
        $class = TargetWidget::class;

        return $class::widget(['colour' => 'red']);
    }

    public function dynamicMethod(): mixed
    {
        return TargetWidget::{$this->method}(['colour' => 'red']);
    }

    public function extraArgument(): string
    {
        return TargetWidget::widget(
            ['colour' => 'red'],
            'extra',
        );
    }

    public function factoryBinding(): string
    {
        return FactoryWidget::widget(['colour' => 'red']);
    }

    public function firstClassCallable(): string
    {
        $create = TargetWidget::widget(...);

        return $create(['colour' => 'red']);
    }

    public function getterOnlyName(): string
    {
        return TargetWidget::widget(['size' => 1]);
    }

    public function instanceForIntProperty(): object
    {
        return TargetWidget::begin(['count' => Instance::of(TypedValue::class)]);
    }

    public function instanceMethodCall(): string
    {
        return (new TargetWidget())->widget(['colour' => 'red']);
    }

    public function mappedBehavior(): string
    {
        return MappedWidget::widget(['shade' => 'dark']);
    }

    public function missingClass(): string
    {
        return \Missing\Widget::widget(['colour' => 'red']);
    }

    public function nonWidgetBinding(): string
    {
        return ObjectBoundWidget::widget(['colour' => 'red']);
    }

    public function nonWidgetClass(): object
    {
        return PlainObject::begin(['name' => 'value']);
    }

    public function nonWidgetLegacyClass(): string
    {
        return TargetWidget::widget(
            [
                '__class' => PlainObject::class,
                'colour' => 'red',
            ],
        );
    }

    public function nullLegacyClass(): string
    {
        return TargetWidget::widget(
            [
                '__class' => null,
                'colour' => 'red',
            ],
        );
    }

    public function objectClass(): string
    {
        $widget = new TargetWidget();

        return $widget::widget(['colour' => 'red']);
    }

    public function optionalKey(): string
    {
        $config = ['name' => 'value'];

        if ($this->toggle) {
            $config['colour'] = 'red';
        }

        return TargetWidget::widget($config);
    }

    public function parentClass(): string
    {
        return parent::widget(['colour' => 'red']);
    }

    public function selfClass(): string
    {
        return self::widget(['colour' => 'red']);
    }

    public function setterBeforeOption(): string
    {
        return TargetWidget::widget(
            [
                'label' => 'value',
                'colour' => 'red',
            ],
        );
    }

    public function setterDefinition(): string
    {
        return SetterBoundWidget::widget(['colour' => 'red']);
    }

    public function singletonBinding(): object
    {
        return SingletonWidget::begin(['colour' => 'red']);
    }

    public function staticClass(): object
    {
        return static::begin(['colour' => 'red']);
    }

    public function traitOverride(): string
    {
        return TraitOverrideWidget::widget(['colour' => 'red']);
    }

    public function uncertainLegacyClass(): string
    {
        return TargetWidget::widget(
            [
                '__class' => $this->toggle ? OtherWidget::class : ChildWidget::class,
                'name' => 'value',
            ],
        );
    }

    public function unionOfShapes(): string
    {
        $config = $this->toggle ? ['colour' => 'red'] : ['shade' => 'dark'];

        return TargetWidget::widget($config);
    }

    public function unknownNamedArgument(): string
    {
        return TargetWidget::widget(options: ['colour' => 'red']);
    }

    public function unpackedArgument(): string
    {
        return TargetWidget::widget(...[['colour' => 'red']]);
    }

    public function unpackedConfiguration(): string
    {
        return TargetWidget::widget(
            [
                ...$this->extra(),
                'colour' => 'red',
            ],
        );
    }

    public function widgetOverride(): string
    {
        return WidgetOverrideWidget::widget(['colour' => 'red']);
    }

    /**
     * @param array{colour: string} $config
     */
    private function documented(array $config): string
    {
        return TargetWidget::widget($config);
    }

    /**
     * @return array<string, mixed>
     */
    private function extra(): array
    {
        return [];
    }
}
