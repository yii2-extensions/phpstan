<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\support\behavior\{
    AbstractBehavior,
    AbstractPlainObject,
    AttachableBehavior,
    BehaviorFactory,
    BehaviorOwner,
    MappedAwayBehavior,
    OtherBehavior,
    PlainClass,
    PublicAttachObject,
    UnboundContract,
};

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\BehaviorAsDefinitionRuntimeTest} test cases.
 */
final class BehaviorAsDefinitionRuntimeProvider
{
    /**
     * Outcome of `Component::__set()` rejecting the supplied value.
     */
    private const string NOT_BEHAVIOR = 'yii\base\InvalidConfigException: Class is not of type yii\base\Behavior or '
        . 'its subclasses';
    /**
     * Outcome of `attachBehavior()` receiving a created object that is not a behavior.
     */
    private const string UNSUPPORTED_OBJECT = 'yii\base\InvalidConfigException: Unsupported configuration type: object';

    /**
     * @phpstan-return iterable<string, array{string, string, string}>
     */
    public static function caseProvider(): iterable
    {
        foreach (self::outcomes() as $method => $cases) {
            foreach ($cases as $case => $outcome) {
                yield "{$method} {$case}" => [$method, $case, $outcome];
            }
        }
    }

    /**
     * Fixture cases not run, because Yii never returns: the container follows the alias chain endlessly.
     *
     * @phpstan-return list<string>
     */
    public static function notRun(): array
    {
        return ['cycle'];
    }

    /**
     * Recorded outcomes by fixture method and case: the attached behavior class, `none`, or the exception class and
     * message.
     *
     * @phpstan-return array<string, array<string, string>>
     */
    public static function outcomes(): array
    {
        return [
            'reported' => [
                'plainClass' => self::NOT_BEHAVIOR,
                'notAttachable' => self::NOT_BEHAVIOR,
                'publicAttach' => self::NOT_BEHAVIOR,
                'component' => self::NOT_BEHAVIOR,
                'behavior' => self::NOT_BEHAVIOR,
                'behaviorClass' => self::NOT_BEHAVIOR,
                'contract' => self::NOT_BEHAVIOR,
                'boundObject' => self::NOT_BEHAVIOR,
                'leadingSlash' => self::NOT_BEHAVIOR,
                'dunderWins' => self::NOT_BEHAVIOR,
                'nullDunder' => self::NOT_BEHAVIOR,
                'nullClass' => self::NOT_BEHAVIOR,
                'emptyArray' => self::NOT_BEHAVIOR,
                'callable' => self::NOT_BEHAVIOR,
                'null' => self::NOT_BEHAVIOR,
                'integer' => self::NOT_BEHAVIOR,
                'finalObject' => self::objectAsArray(BehaviorFactory::class),
                'newObject' => self::objectAsArray(PlainClass::class),
                'mappedAway' => self::UNSUPPORTED_OBJECT,
                'mappedComponent' => self::UNSUPPORTED_OBJECT,
                'spaced' => self::NOT_BEHAVIOR,
                'attachOverride' => self::NOT_BEHAVIOR,
                'params' => self::NOT_BEHAVIOR,
                'constant' => self::NOT_BEHAVIOR,
            ],
            'standAside' => [
                'containerId' => self::NOT_BEHAVIOR,
                'unknownId' => self::NOT_BEHAVIOR,
                'emptyString' => self::NOT_BEHAVIOR,
                'abstract' => 'yii\di\NotInstantiableException: Can not instantiate ' . AbstractBehavior::class . '.',
                'union' => AttachableBehavior::class,
                'objectOrClass' => AttachableBehavior::class,
                'arrayUnion' => AttachableBehavior::class,
                'maybeNullDunder' => self::unknownProperty(AttachableBehavior::class, '__class'),
                'nullDunderBehavior' => self::unknownProperty(AttachableBehavior::class, '__class'),
                'integerClass' => self::NOT_BEHAVIOR,
                'typedObject' => self::objectAsArray(PlainClass::class),
                'closureClassName' => 'Error: Call to undefined method ' . PlainClass::class . '::attach()',
                'factory' => self::UNSUPPORTED_OBJECT,
                'singleton' => self::UNSUPPORTED_OBJECT,
                'invokable' => AttachableBehavior::class,
                'abstractBound' => 'yii\di\NotInstantiableException: Can not instantiate '
                    . AbstractPlainObject::class
                    . '.',
                'interfaceBound' => 'yii\di\NotInstantiableException: Can not instantiate '
                    . UnboundContract::class
                    . '.',
                'attachOverrideMapped' => 'none',
                'setOverride' => 'none',
                'baseObjectOwner' => self::unknownProperty(PublicAttachObject::class, 'as baseObjectOwner'),
                'notAsKey' => self::unknownProperty(BehaviorOwner::class, 'As notAsKey'),
            ],
            'valid' => [
                'attachable' => AttachableBehavior::class,
                'configured' => AttachableBehavior::class,
                'dunder' => AttachableBehavior::class,
                'leadingSlash' => AttachableBehavior::class,
                'instance' => AttachableBehavior::class,
                'closure' => AttachableBehavior::class,
                'firstClassCallable' => AttachableBehavior::class,
                'redirected' => OtherBehavior::class,
                'leadingSlashMapped' => MappedAwayBehavior::class,
            ],
        ];
    }

    private static function objectAsArray(string $class): string
    {
        return "Error: Cannot use object of type {$class} as array";
    }

    private static function unknownProperty(string $class, string $property): string
    {
        return "yii\\base\\UnknownPropertyException: Setting unknown property: {$class}::{$property}";
    }
}
