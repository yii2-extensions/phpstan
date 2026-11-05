<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use TypeError;
use yii\base\UnknownPropertyException;
use yii2\extensions\phpstan\tests\data\rule\traitproperty\{
    CountAction,
    CountObject,
    CountTraitObject,
    CountWidget,
    SecretAction,
    SecretObject,
    SecretWidget,
    TraitPropertyActionController,
    TraitPropertyCreateObject,
    TraitPropertyNewObject,
    TraitPropertyWidget,
    TriggerAction,
    TriggerComponent,
    TriggerWidget,
};

use function sprintf;

/**
 * Data provider for the trait-redeclared property cases of the option rule tests and of
 * {@see \yii2\extensions\phpstan\tests\rules\TraitPropertyOptionRuntimeTest}.
 */
final class TraitPropertyOptionProvider
{
    /**
     * Action IDs of the controller fixture.
     */
    private const array ACTION_IDS = [
        'paintTrigger',
        'plainCount',
        'plainTrigger',
        'protectedSecret',
        'publicSecret',
        'wideCount',
    ];
    /**
     * Value that every `$count` case assigns, as PHPStan describes it.
     */
    private const string COUNT_VALUE = 'array{1, 2}';
    /**
     * Methods of the `Yii::createObject()` and `new` fixtures.
     */
    private const array OBJECT_METHODS = [
        'count',
        'countTrait',
        'inheritedWideCount',
        'nestedWideCount',
        'paintTrigger',
        'publicSecret',
        'secret',
        'trigger',
        'wideCount',
    ];
    /**
     * Methods of the widget fixture.
     */
    private const array WIDGET_METHODS = ['count', 'paintTrigger', 'publicSecret', 'secret', 'trigger', 'wideCount'];

    /**
     * @phpstan-return iterable<string, array{class-string, string, list<array{string|null, string}>}>
     */
    public static function createObjectInvalidOptionTypeProvider(): iterable
    {
        $message = static fn(string $class): string => sprintf(
            'Yii::createObject() sets %s::$count to %s, but the property accepts only int, even with type coercion.',
            $class,
            self::COUNT_VALUE,
        );

        yield from self::methodCases(
            TraitPropertyCreateObject::class,
            self::OBJECT_METHODS,
            [
                'count' => [["'count'", $message(CountObject::class)]],
                'countTrait' => [["'count'", $message(CountTraitObject::class)]],
            ],
        );
    }

    /**
     * @phpstan-return iterable<string, array{class-string, string, list<array{string|null, string}>}>
     */
    public static function createObjectUnknownOptionProvider(): iterable
    {
        $message = static fn(string $class, string $option): string => sprintf(
            'Yii::createObject() sets unknown property %s::$%s: the class has no public property or setter with that '
                . 'name.',
            $class,
            $option,
        );

        yield from self::methodCases(
            TraitPropertyCreateObject::class,
            self::OBJECT_METHODS,
            [
                'secret' => [["'secret'", $message(SecretObject::class, 'secret')]],
                'trigger' => [["'colour'", $message(TriggerComponent::class, 'colour')]],
            ],
        );
    }

    /**
     * @phpstan-return iterable<string, array{class-string, list<array{string|null, string|null, string}>}>
     */
    public static function externalActionInvalidOptionTypeProvider(): iterable
    {
        $controller = TraitPropertyActionController::class;

        yield 'trait-redeclared action properties' => [
            $controller,
            [
                [
                    'plainCount',
                    "'count'",
                    sprintf(
                        "External action 'plainCount' in %s::actions() sets %s::\$count to %s, but the property "
                            . 'accepts only int, even with type coercion.',
                        $controller,
                        CountAction::class,
                        self::COUNT_VALUE,
                    ),
                ],
            ],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{class-string, list<array{string|null, string|null, string}>}>
     */
    public static function externalActionUnknownOptionProvider(): iterable
    {
        $controller = TraitPropertyActionController::class;

        $message = static fn(string $actionId, string $class, string $option): string => sprintf(
            "External action '%s' in %s::actions() sets unknown property %s::$%s: the class has no public property or "
                . 'setter with that name.',
            $actionId,
            $controller,
            $class,
            $option,
        );

        yield 'trait-redeclared action properties' => [
            $controller,
            [
                ['protectedSecret', "'secret'", $message('protectedSecret', SecretAction::class, 'secret')],
                ['plainTrigger', "'colour'", $message('plainTrigger', TriggerAction::class, 'colour')],
            ],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{class-string, string, list<array{string|null, string}>}>
     */
    public static function newObjectInvalidOptionTypeProvider(): iterable
    {
        $message = static fn(string $class): string => sprintf(
            'new %s() sets $count to %s, but the property accepts only int, even with type coercion.',
            $class,
            self::COUNT_VALUE,
        );

        yield from self::methodCases(
            TraitPropertyNewObject::class,
            self::OBJECT_METHODS,
            [
                'count' => [["'count'", $message(CountObject::class)]],
                'countTrait' => [["'count'", $message(CountTraitObject::class)]],
            ],
        );
    }

    /**
     * @phpstan-return iterable<string, array{class-string, string, list<array{string|null, string}>}>
     */
    public static function newObjectUnknownOptionProvider(): iterable
    {
        $message = static fn(string $class, string $option): string => sprintf(
            'new %s() sets unknown property $%s: the class has no public property or setter with that name.',
            $class,
            $option,
        );

        yield from self::methodCases(
            TraitPropertyNewObject::class,
            self::OBJECT_METHODS,
            [
                'secret' => [["'secret'", $message(SecretObject::class, 'secret')]],
                'trigger' => [["'colour'", $message(TriggerComponent::class, 'colour')]],
            ],
        );
    }

    /**
     * @phpstan-return iterable<string, array{string, string, array{class-string, string}|null}>
     */
    public static function runtimeProvider(): iterable
    {
        $typeError = static fn(string $class): array => [
            TypeError::class,
            sprintf('Cannot assign array to property %s::$count of type int', $class),
        ];
        $unknown = static fn(string $class, string $option): array => [
            UnknownPropertyException::class,
            "Setting unknown property: {$class}::{$option}",
        ];

        $rejected = [
            'createObject' => [
                'count' => $typeError(CountObject::class),
                'countTrait' => $typeError(CountTraitObject::class),
                'secret' => $unknown(SecretObject::class, 'secret'),
                'trigger' => $unknown(TriggerComponent::class, 'colour'),
            ],
            'externalAction' => [
                'plainCount' => $typeError(CountAction::class),
                'plainTrigger' => $unknown(TriggerAction::class, 'colour'),
                'protectedSecret' => $unknown(SecretAction::class, 'secret'),
            ],
            'widget' => [
                'count' => $typeError(CountWidget::class),
                'secret' => $unknown(SecretWidget::class, 'secret'),
                'trigger' => $unknown(TriggerWidget::class, 'colour'),
            ],
        ];
        $rejected['new'] = $rejected['createObject'];

        $cases = [
            'createObject' => self::OBJECT_METHODS,
            'externalAction' => self::ACTION_IDS,
            'new' => self::OBJECT_METHODS,
            'widget' => self::WIDGET_METHODS,
        ];

        foreach ($cases as $family => $names) {
            foreach ($names as $name) {
                yield "{$family} {$name}" => [$family, $name, $rejected[$family][$name] ?? null];
            }
        }
    }

    /**
     * @phpstan-return iterable<string, array{class-string, string, list<array{string|null, string}>}>
     */
    public static function widgetInvalidOptionTypeProvider(): iterable
    {
        yield from self::methodCases(
            TraitPropertyWidget::class,
            self::WIDGET_METHODS,
            [
                'count' => [
                    [
                        "'count'",
                        sprintf(
                            '%1$s::widget() sets %1$s::$count to %2$s, but the property accepts only int, even with '
                                . 'type coercion.',
                            CountWidget::class,
                            self::COUNT_VALUE,
                        ),
                    ],
                ],
            ],
        );
    }

    /**
     * @phpstan-return iterable<string, array{class-string, string, list<array{string|null, string}>}>
     */
    public static function widgetUnknownOptionProvider(): iterable
    {
        $message = static fn(string $class, string $option): string => sprintf(
            '%1$s::widget() sets unknown property %1$s::$%2$s: the class has no public property or setter with that '
                . 'name.',
            $class,
            $option,
        );

        yield from self::methodCases(
            TraitPropertyWidget::class,
            self::WIDGET_METHODS,
            [
                'secret' => [["'secret'", $message(SecretWidget::class, 'secret')]],
                'trigger' => [["'colour'", $message(TriggerWidget::class, 'colour')]],
            ],
        );
    }

    /**
     * Returns one case per fixture method, with the expected diagnostics of the method or none.
     *
     * @phpstan-param class-string $fixture
     * @phpstan-param list<string> $methods
     * @phpstan-param array<string, list<array{string|null, string}>> $reported
     *
     * @phpstan-return iterable<string, array{class-string, string, list<array{string|null, string}>}>
     */
    private static function methodCases(string $fixture, array $methods, array $reported): iterable
    {
        foreach ($methods as $method) {
            yield $method => [$fixture, $method, $reported[$method] ?? []];
        }
    }
}
