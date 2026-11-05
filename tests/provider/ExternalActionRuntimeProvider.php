<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use ReflectionClass;
use ReflectionException;
use TypeError;
use yii\base\{Action, InvalidCallException, InvalidConfigException, UnknownPropertyException};
use yii\di\NotInstantiableException;
use yii2\extensions\phpstan\tests\data\rule\action\{
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
    InvalidOptionTypeActionController,
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
use yii2\extensions\phpstan\tests\data\rule\action\hook\HookActionController;
use yii2\extensions\phpstan\tests\support\action\{
    AbstractAction,
    BehaviorsAction,
    ChildAction,
    ConsumingConstructorAction,
    ExtraParamConstructorAction,
    FactoryAction,
    ForwardingConstructorAction,
    MappedAction,
    OtherAction,
    PlainObject,
    SingletonAction,
    TargetAction,
};
use yii2\extensions\phpstan\tests\support\action\hook\{
    HookedControllerAction,
    HookedIdAction,
    PassThroughHookAction,
    TraitHookedChildAction,
    TraitHookedIdAction,
};

use function array_keys;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\ExternalActionRuntimeTest} test cases.
 */
final class ExternalActionRuntimeProvider
{
    /**
     * Controller ID of the fixtures whose map depends on it; other fixtures use `probe`.
     *
     * @phpstan-var array<class-string, non-empty-string>
     */
    public const array CONTROLLER_IDS = [
        MultipleReturnsActionController::class => 'other',
    ];

    /**
     * Outcome of the property hook fixtures, which need PHP 8.4 or later.
     *
     * @phpstan-var array<string, array{class-string, non-empty-string|null}>
     */
    private const array HOOK_OUTCOMES = [
        'hookedId' => [
            HookedIdAction::class,
            null,
        ],
        'hookedIdType' => [
            TypeError::class,
            'Cannot assign string to property ' . HookedIdAction::class . '::$count',
        ],
        'hookedController' => [
            HookedControllerAction::class,
            null,
        ],
        'passThroughHook' => [
            UnknownPropertyException::class,
            'Setting unknown property: ' . PassThroughHookAction::class . '::colour',
        ],
        'traitHookedId' => [
            TraitHookedIdAction::class,
            null,
        ],
        'traitHookedChild' => [
            TraitHookedChildAction::class,
            null,
        ],
    ];

    /**
     * `createAction()` outcome of each action ID, by fixture controller: the class of the created action, or of the
     * thrown exception with the start of its message, or `null` when no action is created.
     *
     * @phpstan-var array<class-string, array<array-key, array{class-string|null, non-empty-string|null}>>
     */
    private const array OUTCOMES = [
        AnonymousActionControllerFactory::class => [
            'unknown' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
        ],
        ClassConstantMapActionController::class => [
            'unknown' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
            'type' => [
                TypeError::class,
                'Cannot assign string to property ' . TargetAction::class . '::$count',
            ],
        ],
        ClosureActionController::class => [
            'valid' => [
                TargetAction::class,
                null,
            ],
        ],
        ConcreteChildActionController::class => [
            'unknown' => [
                TargetAction::class,
                null,
            ],
        ],
        ConcreteParentActionController::class => [
            'unknown' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
        ],
        ConditionalMapActionController::class => [
            'valid' => [
                TargetAction::class,
                null,
            ],
            'unknown' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
        ],
        ConstantMapActionController::class => [
            'unknown' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
            'type' => [
                TypeError::class,
                'Cannot assign string to property ' . TargetAction::class . '::$count',
            ],
        ],
        CreateActionOverrideController::class => [
            'unknown' => [
                TargetAction::class,
                null,
            ],
        ],
        DocMapActionController::class => [
            'unknown' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
        ],
        DynamicIdActionController::class => [
            'probe' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
        ],
        InheritingActionController::class => [
            'unknown' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
        ],
        IntegerIdActionController::class => [
            '0' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
        ],
        InvalidOptionTypeActionController::class => [
            'arrayForString' => [
                TypeError::class,
                'Cannot assign array to property ' . TargetAction::class . '::$name',
            ],
            'arrayForSetter' => [
                TypeError::class,
                TargetAction::class . '::setLabel(): Argument #1',
            ],
            'nonNumericString' => [
                TypeError::class,
                'Cannot assign string to property ' . TargetAction::class . '::$count',
            ],
            'objectForClass' => [
                TypeError::class,
                'Cannot assign stdClass to property ' . TargetAction::class . '::$value',
            ],
            'boundClass' => [
                TypeError::class,
                'Cannot assign array to property ' . OtherAction::class . '::$colour',
            ],
            'legacyClass' => [
                TypeError::class,
                'Cannot assign array to property ' . TargetAction::class . '::$count',
            ],
            'constantDefinition' => [
                TypeError::class,
                'Cannot assign string to property ' . TargetAction::class . '::$count',
            ],
        ],
        MergedMapActionController::class => [
            'unknown' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
        ],
        MultipleReturnsActionController::class => [
            'unknown' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
        ],
        OpenMapActionController::class => [
            'unknown' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
        ],
        OverridingChildActionController::class => [
            'unknown' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
        ],
        StandAsideActionController::class => [
            'getterOnly' => [
                InvalidCallException::class,
                'Setting read-only property: ' . TargetAction::class . '::size',
            ],
            'staticProperty' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetAction::class . '::shared',
            ],
            'behaviorsMethod' => [
                BehaviorsAction::class,
                null,
            ],
            'mappedBehavior' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . MappedAction::class . '::colour',
            ],
            'behaviorAfterOption' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
            'forwardingConstructor' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . ForwardingConstructorAction::class . '::colour',
            ],
            'consumingConstructor' => [
                ConsumingConstructorAction::class,
                null,
            ],
            'extraParamConstructor' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . ExtraParamConstructorAction::class . '::colour',
            ],
            'legacyClassNull' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetAction::class . '::__class',
            ],
            'constructKeyZero' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
            'constructKeyTwo' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
            'constructNamed' => [
                InvalidConfigException::class,
                'Dependencies indexed by name and by position in the same array are not allowed.',
            ],
            'paramsBound' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
            'constructBound' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
            'setterBound' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
            'singleton' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . SingletonAction::class . '::colour',
            ],
            'factory' => [
                FactoryAction::class,
                null,
            ],
            'abstractAction' => [
                NotInstantiableException::class,
                'Can not instantiate ' . AbstractAction::class . '.',
            ],
            'nonAction' => [
                ReflectionException::class,
                'Class ' . PlainObject::class . ' does not have a constructor',
            ],
            'internalClass' => [
                ReflectionException::class,
                'Class stdClass does not have a constructor',
            ],
            'missingClass' => [
                NotInstantiableException::class,
                'Failed to instantiate component or class "Missing\Action".',
            ],
            'dynamicClass' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
            'dynamicKey' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
            'callable' => [
                TargetAction::class,
                null,
            ],
            'nullDefinition' => [
                null,
                null,
            ],
            '' => [
                null,
                null,
            ],
        ],
        TraitActionController::class => [
            'unknown' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
        ],
        UnionMapActionController::class => [
            'unknown' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
        ],
        UnknownOptionActionController::class => [
            'unknown' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
            'multiLine' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
            'baseAction' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . Action::class . '::colour',
            ],
            'inherited' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . ChildAction::class . '::colour',
            ],
            'protectedProperty' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetAction::class . '::internal',
            ],
            'caseMismatch' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . TargetAction::class . '::Name',
            ],
            'optionBeforeSetter' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
            'legacyClass' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
            'legacyClassWins' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . OtherAction::class . '::name',
            ],
            'boundClass' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . OtherAction::class . '::name',
            ],
            'aliasId' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . OtherAction::class . '::name',
            ],
            'optionsBound' => [
                UnknownPropertyException::class,
                'Setting unknown property: ' . ChildAction::class . '::colour',
            ],
            'constantKey' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
            'constantDefinition' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
            'variableDefinition' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
            'dynamicValue' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
        ],
        UnpackedMapActionController::class => [
            'unknown' => [
                UnknownPropertyException::class,
                self::UNKNOWN_COLOUR,
            ],
        ],
        ValidActionController::class => [
            'properties' => [
                TargetAction::class,
                null,
            ],
            'setter' => [
                TargetAction::class,
                null,
            ],
            'coercedValue' => [
                TargetAction::class,
                null,
            ],
            'classOnly' => [
                TargetAction::class,
                null,
            ],
            'legacyClass' => [
                TargetAction::class,
                null,
            ],
            'legacyClassWins' => [
                TargetAction::class,
                null,
            ],
            'event' => [
                TargetAction::class,
                null,
            ],
            'behavior' => [
                TargetAction::class,
                null,
            ],
            'idOption' => [
                TargetAction::class,
                null,
            ],
            'controllerOption' => [
                TargetAction::class,
                null,
            ],
            'boundClass' => [
                OtherAction::class,
                null,
            ],
            'aliasId' => [
                OtherAction::class,
                null,
            ],
            'optionsBound' => [
                ChildAction::class,
                null,
            ],
            'baseAction' => [
                Action::class,
                null,
            ],
            'classString' => [
                TargetAction::class,
                null,
            ],
        ],
    ];

    /**
     * Message start of the rejected `colour` option of {@see TargetAction}.
     */
    private const string UNKNOWN_COLOUR = 'Setting unknown property: ' . TargetAction::class . '::colour';

    /**
     * Returns the recorded action IDs of a fixture controller, in map order.
     *
     * @phpstan-param class-string $controller
     *
     * @return list<string>
     */
    public static function actionIds(string $controller): array
    {
        $ids = [];

        foreach (array_keys(self::outcomes()[$controller] ?? []) as $actionId) {
            $ids[] = (string) $actionId;
        }

        return $ids;
    }

    /**
     * @phpstan-return iterable<string, array{class-string, string, class-string|null, non-empty-string|null}>
     */
    public static function actionProvider(): iterable
    {
        foreach (self::controllers() as $controller) {
            foreach (self::actionIds($controller) as $actionId) {
                [$outcome, $message] = self::outcome($controller, $actionId);

                yield "{$controller} {$actionId}" => [$controller, $actionId, $outcome, $message];
            }
        }
    }

    /**
     * Returns the fixture controllers with recorded outcomes, without the property hook fixture.
     *
     * @return list<class-string>
     */
    public static function controllers(): array
    {
        return array_keys(self::outcomes());
    }

    /**
     * @phpstan-return iterable<string, array{class-string, string, class-string, non-empty-string|null}>
     */
    public static function hookProvider(): iterable
    {
        foreach (self::HOOK_OUTCOMES as $actionId => [$outcome, $message]) {
            yield $actionId => [HookActionController::class, $actionId, $outcome, $message];
        }
    }

    /**
     * Returns the recorded outcome of an action ID on the installed Yii line.
     *
     * @phpstan-param class-string $controller
     *
     * @return array{class-string|null, non-empty-string|null}
     */
    public static function outcome(string $controller, string $actionId): array
    {
        $outcomes = $controller === HookActionController::class ? self::HOOK_OUTCOMES : self::outcomes()[$controller] ?? [];

        return $outcomes[$actionId] ?? [null, 'unrecorded'];
    }

    /**
     * Returns the outcomes with the `module` option, which only Yii 22 assigns through `Action::setModule()`.
     *
     * @phpstan-return array<class-string, array<array-key, array{class-string|null, non-empty-string|null}>>
     */
    private static function outcomes(): array
    {
        $outcomes = self::OUTCOMES;

        $outcomes[ModuleOptionActionController::class] = [
            'module' => (new ReflectionClass(Action::class))->hasMethod('setModule')
                ? [TargetAction::class, null]
                : [UnknownPropertyException::class, 'Setting unknown property: ' . TargetAction::class . '::module'],
        ];

        return $outcomes;
    }
}
