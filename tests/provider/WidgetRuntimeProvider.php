<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use Error;
use RuntimeException;
use TypeError;
use yii\base\{InvalidCallException, InvalidConfigException, UnknownPropertyException, Widget};
use yii\di\NotInstantiableException;
use yii2\extensions\phpstan\tests\data\rule\{
    WidgetInvalidOptionType,
    WidgetStandAside,
    WidgetUnknownOption,
    WidgetValidOption,
};
use yii2\extensions\phpstan\tests\support\createobject\TypedValue;
use yii2\extensions\phpstan\tests\support\widget\{
    AbstractWidget,
    BeginOverrideWidget,
    BehaviorsWidget,
    ChildWidget,
    CustomConstructorWidget,
    FactoryWidget,
    MappedWidget,
    OtherWidget,
    PlainObject,
    RunFailingWidget,
    SingletonWidget,
    TargetWidget,
    TraitOverrideWidget,
    WidgetOverrideWidget,
};

use function sprintf;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\WidgetRuntimeTest} test cases.
 */
final class WidgetRuntimeProvider
{
    /**
     * Outcome of each fixture method, by fixture class and method name.
     *
     * @phpstan-var array<class-string, array<string, array{class-string, non-empty-string|null}>>
     */
    private const array OUTCOMES = [
        WidgetInvalidOptionType::class => [
            'arrayForStringProperty' => [
                TypeError::class,
                'Cannot assign array to property ' . TargetWidget::class . '::$name',
            ],
            'arrayForStringSetter' => [
                TypeError::class,
                TargetWidget::class . '::setLabel(): Argument #1',
            ],
            'boundClass' => [
                TypeError::class,
                'Cannot assign array to property ' . OtherWidget::class . '::$colour',
            ],
            'constantConfiguration' => [
                TypeError::class,
                'Cannot assign string to property ' . TargetWidget::class . '::$count',
            ],
            'constructArgument' => [
                TypeError::class,
                'Cannot assign string to property ' . TargetWidget::class . '::$count',
            ],
            'inheritedProperty' => [
                TypeError::class,
                'Cannot assign string to property ' . TargetWidget::class . '::$count',
            ],
            'legacyClassKey' => [
                TypeError::class,
                'Cannot assign array to property ' . OtherWidget::class . '::$colour',
            ],
            'namedConfig' => [
                TypeError::class,
                'Cannot assign array to property ' . TargetWidget::class . '::$count',
            ],
            'nonNumericStringForIntProperty' => [
                TypeError::class,
                'Cannot assign string to property ' . TargetWidget::class . '::$count',
            ],
            'objectForClassProperty' => [
                TypeError::class,
                'Cannot assign ' . OtherWidget::class . ' to property ' . TargetWidget::class . '::$value',
            ],
        ],
        WidgetStandAside::class => [
            'abstractClass' => [
                NotInstantiableException::class,
                'Can not instantiate ' . AbstractWidget::class . '.',
            ],
            'beginOverride' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . BeginOverrideWidget::class . '::colour',
            ],
            'behaviorAfterOption' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'behaviorsOverride' => [BehaviorsWidget::class, null],
            'customConstructor' => [
                CustomConstructorWidget::class,
                null,
            ],
            'documentedConfiguration' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'dynamicClass' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'dynamicMethod' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'extraArgument' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'factoryBinding' => [FactoryWidget::class, null],
            'firstClassCallable' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'getterOnlyName' => [
                InvalidCallException::class,
                'Setting read-only property: ' . TargetWidget::class . '::size',
            ],
            'instanceForIntProperty' => [
                TypeError::class,
                'Cannot assign ' . TypedValue::class . ' to property ' . TargetWidget::class . '::$count',
            ],
            'instanceMethodCall' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'mappedBehavior' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . MappedWidget::class . '::shade',
            ],
            'missingClass' => [
                Error::class,
                'Class "Missing\\Widget" not found',
            ],
            'nonWidgetBinding' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . PlainObject::class . '::colour',
            ],
            'nonWidgetLegacyClass' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . PlainObject::class . '::colour',
            ],
            'nonWidgetClass' => [
                Error::class,
                'Call to undefined method ' . PlainObject::class . '::begin()',
            ],
            'nullLegacyClass' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::__class',
            ],
            'objectClass' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'optionalKey' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'parentClass' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . WidgetStandAside::class . '::colour',
            ],
            'selfClass' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . WidgetStandAside::class . '::colour',
            ],
            'setterBeforeOption' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'setterDefinition' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'singletonBinding' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . SingletonWidget::class . '::colour',
            ],
            'staticClass' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . WidgetStandAside::class . '::colour',
            ],
            'traitOverride' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TraitOverrideWidget::class . '::colour',
            ],
            'uncertainLegacyClass' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . OtherWidget::class . '::name',
            ],
            'unionOfShapes' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'unknownNamedArgument' => [
                Error::class,
                'Unknown named parameter $options',
            ],
            'unpackedArgument' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'unpackedConfiguration' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'widgetOverride' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . WidgetOverrideWidget::class . '::colour',
            ],
        ],
        WidgetUnknownOption::class => [
            'aliasedClass' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'baseWidget' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . Widget::class . '::colour',
            ],
            'beginOfWidgetOverride' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . WidgetOverrideWidget::class . '::colour',
            ],
            'boundClassBegin' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . OtherWidget::class . '::name',
            ],
            'boundClassWidget' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . OtherWidget::class . '::name',
            ],
            'caseMismatchedProperty' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::Name',
            ],
            'classKeyOverwritten' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'constantConfiguration' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'constantKey' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'constructArgumentClassKey' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::class',
            ],
            'constructArgumentOption' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'inheritedClass' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . ChildWidget::class . '::colour',
            ],
            'legacyClassKey' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . OtherWidget::class . '::name',
            ],
            'localVariable' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'namedConfigBegin' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'namedConfigWidget' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'optionBeforeSetter' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'optionsBoundClass' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . ChildWidget::class . '::colour',
            ],
            'paramsBoundClass' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'protectedProperty' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::internal',
            ],
            'unknownOptionBegin' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'unknownOptionWidget' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetWidget::class . '::colour',
            ],
            'widgetOfBeginOverride' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . BeginOverrideWidget::class . '::colour',
            ],
        ],
        WidgetValidOption::class => [
            'boundClassMembers' => [
                OtherWidget::class,
                null,
            ],
            'classKeyIgnored' => [
                TargetWidget::class,
                null,
            ],
            'constantConfiguration' => [
                TargetWidget::class,
                null,
            ],
            'constructArgumentConfiguration' => [
                TargetWidget::class,
                null,
            ],
            'constructArgumentReplaced' => [
                TargetWidget::class,
                null,
            ],
            'emptyConfiguration' => [
                TargetWidget::class,
                null,
            ],
            'eventAndBehavior' => [
                TargetWidget::class,
                null,
            ],
            'inheritedMembers' => [
                ChildWidget::class,
                null,
            ],
            'initFailsAfterAssignment' => [
                InvalidConfigException::class,
                'The "name" option is required.',
            ],
            'instanceResolved' => [
                TargetWidget::class,
                null,
            ],
            'legacyClassKey' => [
                ChildWidget::class,
                null,
            ],
            'noConfiguration' => [
                TargetWidget::class,
                null,
            ],
            'optionsBoundClass' => [
                ChildWidget::class,
                null,
            ],
            'paramsBoundClass' => [
                TargetWidget::class,
                null,
            ],
            'propertiesAndSettersBegin' => [
                TargetWidget::class,
                null,
            ],
            'propertiesAndSettersWidget' => [
                TargetWidget::class,
                null,
            ],
            'runFailsAfterBegin' => [
                RunFailingWidget::class,
                null,
            ],
            'runFailsInWidget' => [
                RuntimeException::class,
                'Run reached.',
            ],
            'weakCoercion' => [
                TargetWidget::class,
                null,
            ],
        ],
    ];

    /**
     * Returns the fixture classes whose methods have recorded outcomes.
     *
     * @phpstan-return list<class-string>
     */
    public static function fixtures(): array
    {
        return [
            WidgetInvalidOptionType::class,
            WidgetStandAside::class,
            WidgetUnknownOption::class,
            WidgetValidOption::class,
        ];
    }

    /**
     * @phpstan-return iterable<string, array{class-string, string, class-string, non-empty-string|null}>
     */
    public static function methodProvider(): iterable
    {
        foreach (self::OUTCOMES as $fixture => $methods) {
            foreach ($methods as $method => [$outcome, $message]) {
                yield "{$fixture}::{$method}" => [$fixture, $method, $outcome, $message];
            }
        }
    }

    /**
     * Returns the method names recorded for a fixture, in declaration order of the record.
     *
     * @phpstan-param class-string $fixture
     *
     * @phpstan-return list<string>
     */
    public static function methods(string $fixture): array
    {
        $methods = [];

        foreach (self::OUTCOMES[$fixture] ?? [] as $method => $outcome) {
            $methods[] = $method;
        }

        return $methods;
    }

    /**
     * Returns the recorded outcome of a fixture method.
     *
     * @phpstan-param class-string $fixture
     *
     * @phpstan-return array{class-string, non-empty-string|null}
     */
    public static function outcome(string $fixture, string $method): array
    {
        return self::OUTCOMES[$fixture][$method] ?? throw new RuntimeException(
            sprintf('No outcome is recorded for "%s::%s()".', $fixture, $method),
        );
    }
}
