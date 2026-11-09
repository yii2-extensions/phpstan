<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii\base\Behavior;
use yii2\extensions\phpstan\tests\data\rule\behavior\OverridingAttachOwner;
use yii2\extensions\phpstan\tests\support\behavior\{
    BehaviorContract,
    BehaviorFactory,
    BehaviorOwner,
    BoundToBehaviorObject,
    ComponentBoundBehavior,
    MappedAwayBehavior,
    NotAttachableObject,
    NotBehaviorComponent,
    PlainClass,
    PublicAttachObject,
};

use function sprintf;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\CreateObjectInvalidAsDefinitionRuleTest} test cases.
 */
final class BehaviorInvalidAsDefinitionRuleProvider
{
    /**
     * Returns the expected diagnostics of `AsOptions::reported()`, in report order.
     *
     * @phpstan-return list<array{string, string|null, string}>
     */
    public static function reported(): array
    {
        return [
            self::classCase('plainClass', PlainClass::class),
            self::classCase('notAttachable', NotAttachableObject::class),
            self::classCase('publicAttach', PublicAttachObject::class),
            self::classCase('component', NotBehaviorComponent::class),
            self::classCase('behavior', Behavior::class),
            self::classCase('behaviorClass', Behavior::class),
            self::classCase('contract', BehaviorContract::class),
            self::classCase('boundObject', BoundToBehaviorObject::class),
            self::classCase('leadingSlash', PlainClass::class),
            self::classCase('dunderWins', PlainClass::class),
            self::classCase('nullDunder', PlainClass::class),
            self::arrayCase('nullClass'),
            self::arrayCase('emptyArray'),
            self::arrayCase('callable'),
            self::valueCase('null', 'null'),
            self::valueCase('integer', 'int'),
            self::valueCase('finalObject', BehaviorFactory::class),
            self::valueCase('newObject', PlainClass::class),
            self::resolvedCase('mappedAway', MappedAwayBehavior::class, NotAttachableObject::class),
            self::resolvedCase('mappedComponent', ComponentBoundBehavior::class, NotBehaviorComponent::class),
            [
                "'as  spaced ' =>",
                null,
                self::classMessage("'as  spaced '", BehaviorOwner::class, PlainClass::class),
            ],
            [
                "'as attachOverride' =>",
                null,
                self::classMessage("'as attachOverride'", OverridingAttachOwner::class, PlainClass::class),
            ],
            self::classCase('params', PlainClass::class),
            [
                'createObject(self::CONSTANT)',
                null,
                self::classMessage("'as constant'", BehaviorOwner::class, PlainClass::class),
            ],
        ];
    }

    /**
     * @phpstan-return array{string, null, string}
     */
    private static function arrayCase(string $name): array
    {
        return [
            "'as {$name}' =>",
            null,
            sprintf(
                "Yii::createObject() option 'as %s' of %s is a configuration array without a non-null '__class' or "
                . "'class' element, which yii\\base\\Component::__set() rejects.",
                $name,
                BehaviorOwner::class,
            ),
        ];
    }

    /**
     * @phpstan-return array{string, null, string}
     */
    private static function classCase(string $name, string $class): array
    {
        return [
            "'as {$name}' =>",
            null,
            self::classMessage("'as {$name}'", BehaviorOwner::class, $class),
        ];
    }

    /**
     * Returns the expected message for an option naming a class that `Component::__set()` rejects.
     *
     * @param string $option Quoted option name.
     */
    private static function classMessage(string $option, string $owner, string $class): string
    {
        return sprintf(
            'Yii::createObject() option %s of %s uses %s, which yii\base\Component::__set() rejects: it is not a '
            . 'subclass of yii\base\Behavior.',
            $option,
            $owner,
            $class,
        );
    }

    /**
     * @phpstan-return array{string, null, string}
     */
    private static function resolvedCase(string $name, string $class, string $resolved): array
    {
        return [
            "'as {$name}' =>",
            null,
            sprintf(
                "Yii::createObject() option 'as %s' of %s uses %s, which the container resolves to %s; Yii cannot "
                . 'attach it: it is not a yii\base\Behavior or an invocable object.',
                $name,
                BehaviorOwner::class,
                $class,
                $resolved,
            ),
        ];
    }

    /**
     * @phpstan-return array{string, null, string}
     */
    private static function valueCase(string $name, string $type): array
    {
        return [
            "'as {$name}' =>",
            null,
            sprintf(
                "Yii::createObject() option 'as %s' of %s is %s, which yii\\base\\Component::__set() rejects: it is "
                . 'not a yii\base\Behavior, a Closure, a class name or a configuration array.',
                $name,
                BehaviorOwner::class,
                $type,
            ),
        ];
    }
}
