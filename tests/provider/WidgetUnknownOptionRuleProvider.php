<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii\base\Widget;
use yii2\extensions\phpstan\tests\data\rule\WidgetUnknownOption;
use yii2\extensions\phpstan\tests\support\widget\{
    BeginOverrideWidget,
    BoundWidget,
    ChildWidget,
    OptionsBoundWidget,
    OtherWidget,
    ParamsBoundWidget,
    TargetWidget,
    WidgetOverrideWidget,
};

use function sprintf;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\WidgetUnknownOptionRuleTest} test cases.
 */
final class WidgetUnknownOptionRuleProvider
{
    /**
     * @phpstan-return iterable<string, array{class-string, string, list<array{string|null, string}>}>
     */
    public static function reportedProvider(): iterable
    {
        $fixture = WidgetUnknownOption::class;

        yield 'imported alias' => [
            $fixture,
            'aliasedClass',
            [["'colour'", self::message(TargetWidget::class, 'widget', TargetWidget::class, 'colour')]],
        ];
        yield 'base widget' => [
            $fixture,
            'baseWidget',
            [["'colour'", self::message(Widget::class, 'widget', Widget::class, 'colour')]],
        ];
        yield 'begin() of a widget() override' => [
            $fixture,
            'beginOfWidgetOverride',
            [["'colour'", self::message(WidgetOverrideWidget::class, 'begin', WidgetOverrideWidget::class, 'colour')]],
        ];
        yield 'begin() of a container substitution' => [
            $fixture,
            'boundClassBegin',
            [["'name'", self::message(BoundWidget::class, 'begin', OtherWidget::class, 'name')]],
        ];
        yield 'widget() of a container substitution' => [
            $fixture,
            'boundClassWidget',
            [["'name'", self::message(BoundWidget::class, 'widget', OtherWidget::class, 'name')]],
        ];
        yield 'case-mismatched property' => [
            $fixture,
            'caseMismatchedProperty',
            [["'Name'", self::message(TargetWidget::class, 'widget', TargetWidget::class, 'Name')]],
        ];
        yield 'overwritten class key' => [
            $fixture,
            'classKeyOverwritten',
            [["'colour'", self::message(TargetWidget::class, 'widget', TargetWidget::class, 'colour')]],
        ];
        yield 'constant configuration' => [
            $fixture,
            'constantConfiguration',
            [[null, self::message(TargetWidget::class, 'widget', TargetWidget::class, 'colour')]],
        ];
        yield 'constant key' => [
            $fixture,
            'constantKey',
            [['self::OPTION', self::message(TargetWidget::class, 'begin', TargetWidget::class, 'colour')]],
        ];
        yield 'class key of a constructor argument' => [
            $fixture,
            'constructArgumentClassKey',
            [["'class'", self::message(TargetWidget::class, 'widget', TargetWidget::class, 'class')]],
        ];
        yield 'option of a constructor argument' => [
            $fixture,
            'constructArgumentOption',
            [["'colour'", self::message(TargetWidget::class, 'begin', TargetWidget::class, 'colour')]],
        ];
        yield 'inherited class' => [
            $fixture,
            'inheritedClass',
            [["'colour'", self::message(ChildWidget::class, 'widget', ChildWidget::class, 'colour')]],
        ];
        yield 'legacy class key' => [
            $fixture,
            'legacyClassKey',
            [["'name'", self::message(TargetWidget::class, 'begin', OtherWidget::class, 'name')]],
        ];
        yield 'local variable' => [
            $fixture,
            'localVariable',
            [[null, self::message(TargetWidget::class, 'begin', TargetWidget::class, 'colour')]],
        ];
        yield 'named begin() configuration' => [
            $fixture,
            'namedConfigBegin',
            [["'colour'", self::message(TargetWidget::class, 'begin', TargetWidget::class, 'colour')]],
        ];
        yield 'named widget() configuration' => [
            $fixture,
            'namedConfigWidget',
            [["'colour'", self::message(TargetWidget::class, 'widget', TargetWidget::class, 'colour')]],
        ];
        yield 'option before a setter' => [
            $fixture,
            'optionBeforeSetter',
            [["'colour'", self::message(TargetWidget::class, 'widget', TargetWidget::class, 'colour')]],
        ];
        yield 'definition with a plain property option' => [
            $fixture,
            'optionsBoundClass',
            [["'colour'", self::message(OptionsBoundWidget::class, 'widget', ChildWidget::class, 'colour')]],
        ];
        yield 'definition with a configuration argument' => [
            $fixture,
            'paramsBoundClass',
            [["'colour'", self::message(ParamsBoundWidget::class, 'begin', TargetWidget::class, 'colour')]],
        ];
        yield 'protected property' => [
            $fixture,
            'protectedProperty',
            [["'internal'", self::message(TargetWidget::class, 'widget', TargetWidget::class, 'internal')]],
        ];
        yield 'begin() option' => [
            $fixture,
            'unknownOptionBegin',
            [["'colour'", self::message(TargetWidget::class, 'begin', TargetWidget::class, 'colour')]],
        ];
        yield 'widget() option on its own line' => [
            $fixture,
            'unknownOptionWidget',
            [["'colour'", self::message(TargetWidget::class, 'widget', TargetWidget::class, 'colour')]],
        ];
        yield 'widget() of a begin() override' => [
            $fixture,
            'widgetOfBeginOverride',
            [["'colour'", self::message(BeginOverrideWidget::class, 'widget', BeginOverrideWidget::class, 'colour')]],
        ];
    }

    /**
     * Returns the expected message for an option that the created widget can't assign.
     */
    private static function message(string $calledClass, string $method, string $class, string $option): string
    {
        return sprintf(
            '%s::%s() sets unknown property %s::$%s: the class has no public property or setter with that name.',
            $calledClass,
            $method,
            $class,
            $option,
        );
    }
}
