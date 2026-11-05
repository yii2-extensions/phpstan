<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\data\rule\action\{
    AbstractParentActionController,
    ActionsTrait,
    AnonymousActionControllerFactory,
    ClassConstantMapActionController,
    ClosureActionController,
    ConcreteChildActionController,
    ConditionalMapActionController,
    ConstantMapActionController,
    CreateActionOverrideController,
    DocMapActionController,
    DynamicIdActionController,
    InheritingActionController,
    IntegerIdActionController,
    InvalidOptionTypeActionController,
    MergedMapActionController,
    OpenMapActionController,
    OverridingChildActionController,
    StandAsideActionController,
    TraitActionController,
    UnionMapActionController,
    UnpackedMapActionController,
    ValidActionController,
};
use yii2\extensions\phpstan\tests\support\action\{OtherAction, TargetAction};
use yii2\extensions\phpstan\tests\support\createobject\TypedValue;

use function sprintf;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\ExternalActionInvalidOptionTypeRuleTest} test cases.
 */
final class ExternalActionInvalidOptionTypeRuleProvider
{
    /**
     * @phpstan-return iterable<string, array{class-string, list<array{string|null, string|null, string}>}>
     */
    public static function reportedProvider(): iterable
    {
        $controller = InvalidOptionTypeActionController::class;
        $target = TargetAction::class;

        yield 'literal definitions of a non-final controller' => [
            $controller,
            [
                [
                    'arrayForString',
                    "'name'",
                    self::message(
                        'arrayForString',
                        $controller,
                        $target,
                        'name',
                        'array{}',
                        'the property',
                        'string',
                    ),
                ],
                [
                    'arrayForSetter',
                    "'label'",
                    self::message(
                        'arrayForSetter',
                        $controller,
                        $target,
                        'label',
                        'array{}',
                        "{$target}::setLabel()",
                        'string',
                    ),
                ],
                [
                    'nonNumericString',
                    "'count'",
                    self::message(
                        'nonNumericString',
                        $controller,
                        $target,
                        'count',
                        "'all'",
                        'the property',
                        'int',
                    ),
                ],
                [
                    'objectForClass',
                    "'value'",
                    self::message(
                        'objectForClass',
                        $controller,
                        $target,
                        'value',
                        'stdClass',
                        'the property',
                        TypedValue::class . '|null',
                    ),
                ],
                [
                    'boundClass',
                    "'colour'",
                    self::message(
                        'boundClass',
                        $controller,
                        OtherAction::class,
                        'colour',
                        'array{}',
                        'the property',
                        'string',
                    ),
                ],
                [
                    'legacyClass',
                    "'count'",
                    self::message(
                        'legacyClass',
                        $controller,
                        $target,
                        'count',
                        'array{}',
                        'the property',
                        'int',
                    ),
                ],
                [
                    'constantDefinition',
                    null,
                    self::message(
                        'constantDefinition',
                        $controller,
                        $target,
                        'count',
                        "'all'",
                        'the property',
                        'int',
                    ),
                ],
            ],
        ];
        yield 'whole map from a constant-known variable' => [
            ConstantMapActionController::class,
            [
                [
                    null,
                    null,
                    self::message(
                        'type',
                        ConstantMapActionController::class,
                        $target,
                        'count',
                        "'all'",
                        'the property',
                        'int',
                    ),
                ],
            ],
        ];
        yield 'whole map from a class constant' => [
            ClassConstantMapActionController::class,
            [
                [
                    null,
                    null,
                    self::message(
                        'type',
                        ClassConstantMapActionController::class,
                        $target,
                        'count',
                        "'all'",
                        'the property',
                        'int',
                    ),
                ],
            ],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{list<class-string>}>
     */
    public static function standAsideProvider(): iterable
    {
        yield 'abstract controller' => [[AbstractParentActionController::class]];
        yield 'anonymous controller' => [[AnonymousActionControllerFactory::class]];
        yield 'closure and arrow function in actions()' => [[ClosureActionController::class]];
        yield 'child of a reported concrete controller' => [[ConcreteChildActionController::class]];
        yield 'conditional map' => [[ConditionalMapActionController::class]];
        yield 'createAction() override' => [[CreateActionOverrideController::class]];
        yield 'map typed only by PHPDoc' => [[DocMapActionController::class]];
        yield 'dynamic action ID' => [[DynamicIdActionController::class]];
        yield 'actions() inherited from an abstract parent' => [[InheritingActionController::class]];
        yield 'integer action ID' => [[IntegerIdActionController::class]];
        yield 'map merged with the parent map' => [[MergedMapActionController::class]];
        yield 'map of unknown keys extended' => [[OpenMapActionController::class]];
        yield 'createAction() override in the parent' => [[OverridingChildActionController::class]];
        yield 'definitions whose assignment path is not proven' => [[StandAsideActionController::class]];
        yield 'actions() from a trait' => [[TraitActionController::class, ActionsTrait::class]];
        yield 'union of two maps' => [[UnionMapActionController::class]];
        yield 'map unpacking the parent map' => [[UnpackedMapActionController::class]];
        yield 'valid definitions' => [[ValidActionController::class]];
    }

    /**
     * Returns the expected message for an option value that the created action never accepts.
     */
    private static function message(
        string $actionId,
        string $controller,
        string $class,
        string $option,
        string $actual,
        string $member,
        string $expected,
    ): string {
        return sprintf(
            "External action '%s' in %s::actions() sets %s::$%s to %s, but %s accepts only %s, even with type coercion.",
            $actionId,
            $controller,
            $class,
            $option,
            $actual,
            $member,
            $expected,
        );
    }
}
