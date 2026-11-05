<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use ReflectionClass;
use yii\base\Action;
use yii2\extensions\phpstan\tests\data\rule\action\{
    AbstractParentActionController,
    ActionsTrait,
    AnonymousActionControllerFactory,
    ClassConstantMapActionController,
    ClosureActionController,
    ConcreteChildActionController,
    ConcreteParentActionController,
    ConditionalMapActionController,
    ConstantMapActionController,
    CreateActionOverrideController,
    DocMapActionController,
    DynamicIdActionController,
    InheritingActionController,
    IntegerIdActionController,
    MergedMapActionController,
    ModuleOptionActionController,
    MultipleReturnsActionController,
    OpenMapActionController,
    OverridingChildActionController,
    StandAsideActionController,
    TraitActionController,
    UnionMapActionController,
    UnknownOptionActionController,
    UnpackedMapActionController,
    ValidActionController,
};
use yii2\extensions\phpstan\tests\support\action\{ChildAction, OtherAction, TargetAction};

use function sprintf;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\ExternalActionUnknownOptionRuleTest} test cases.
 */
final class ExternalActionUnknownOptionRuleProvider
{
    /**
     * Returns the expected message for an option that the created action can't assign.
     */
    public static function message(string $actionId, string $controller, string $class, string $option): string
    {
        return sprintf(
            "External action '%s' in %s::actions() sets unknown property %s::$%s: the class has no public property or "
            . 'setter with that name.',
            $actionId,
            $controller,
            $class,
            $option,
        );
    }

    /**
     * @phpstan-return iterable<string, array{class-string, list<array{string|null, string|null, string}>}>
     */
    public static function reportedProvider(): iterable
    {
        $controller = UnknownOptionActionController::class;

        yield 'literal definitions of a final controller' => [
            $controller,
            [
                [
                    'unknown',
                    "'colour'",
                    self::message('unknown', $controller, TargetAction::class, 'colour'),
                ],
                [
                    'multiLine',
                    "'colour'",
                    self::message('multiLine', $controller, TargetAction::class, 'colour'),
                ],
                [
                    'baseAction',
                    "'colour'",
                    self::message('baseAction', $controller, Action::class, 'colour'),
                ],
                [
                    'inherited',
                    "'colour'",
                    self::message('inherited', $controller, ChildAction::class, 'colour'),
                ],
                [
                    'protectedProperty',
                    "'internal'",
                    self::message('protectedProperty', $controller, TargetAction::class, 'internal'),
                ],
                [
                    'caseMismatch',
                    "'Name'",
                    self::message('caseMismatch', $controller, TargetAction::class, 'Name'),
                ],
                [
                    'optionBeforeSetter',
                    "'colour'",
                    self::message('optionBeforeSetter', $controller, TargetAction::class, 'colour'),
                ],
                [
                    'legacyClass',
                    "'colour'",
                    self::message('legacyClass', $controller, TargetAction::class, 'colour'),
                ],
                [
                    'legacyClassWins',
                    "'name'",
                    self::message('legacyClassWins', $controller, OtherAction::class, 'name'),
                ],
                [
                    'boundClass',
                    "'name'",
                    self::message('boundClass', $controller, OtherAction::class, 'name'),
                ],
                [
                    'aliasId',
                    "'name'",
                    self::message('aliasId', $controller, OtherAction::class, 'name'),
                ],
                [
                    'optionsBound',
                    "'colour'",
                    self::message('optionsBound', $controller, ChildAction::class, 'colour'),
                ],
                [
                    'constantKey',
                    'self::OPTION',
                    self::message('constantKey', $controller, TargetAction::class, 'colour'),
                ],
                [
                    'constantDefinition',
                    null,
                    self::message('constantDefinition', $controller, TargetAction::class, 'colour'),
                ],
                [
                    'variableDefinition',
                    null,
                    self::message('variableDefinition', $controller, TargetAction::class, 'colour'),
                ],
                [
                    'dynamicValue',
                    "'colour'",
                    self::message('dynamicValue', $controller, TargetAction::class, 'colour'),
                ],
            ],
        ];
        yield 'non-final concrete controller' => [
            ConcreteParentActionController::class,
            [
                [
                    'unknown',
                    "'colour'",
                    self::message('unknown', ConcreteParentActionController::class, TargetAction::class, 'colour'),
                ],
            ],
        ];
        yield 'whole map from a constant-known variable' => [
            ConstantMapActionController::class,
            [
                [
                    null,
                    null,
                    self::message('unknown', ConstantMapActionController::class, TargetAction::class, 'colour'),
                ],
            ],
        ];
        yield 'whole map from a class constant' => [
            ClassConstantMapActionController::class,
            [
                [
                    null,
                    null,
                    self::message('unknown', ClassConstantMapActionController::class, TargetAction::class, 'colour'),
                ],
            ],
        ];
        yield 'first of two returns' => [
            MultipleReturnsActionController::class,
            [
                [
                    'unknown',
                    "'colour'",
                    self::message('unknown', MultipleReturnsActionController::class, TargetAction::class, 'colour'),
                ],
            ],
        ];
        // only Yii 22 declares `Action::setModule()`
        yield 'module option' => [
            ModuleOptionActionController::class,
            (new ReflectionClass(Action::class))->hasMethod('setModule')
                ? []
                : [
                    [
                        'module',
                        "'module'",
                        self::message('module', ModuleOptionActionController::class, TargetAction::class, 'module'),
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
}
