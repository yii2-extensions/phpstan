<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use yii\base\Component;
use yii2\extensions\phpstan\tests\support\behavior\{
    AttachableBehavior,
    BehaviorOwner,
    MappedAwayBehavior,
    NotAttachableObject,
    PlainClass,
    ProtectedAttachObject,
    StaticAttachObject,
    StaticAttachOnly,
};

/**
 * Calls of `attachBehavior()` and `attachBehaviors()` grouped by expected outcome: reported, valid and stood aside.
 */
final class AttachCalls
{
    private const array DEFINITIONS = ['constant' => NotAttachableObject::class];

    public function reported(BehaviorOwner $owner, Component $component, string $name): void
    {
        $owner->attachBehavior(
            'notAttachable',
            NotAttachableObject::class,
        );
        $owner->attachBehavior(
            'mappedAway',
            MappedAwayBehavior::class,
        );
        $owner->attachBehavior(
            'noClass',
            ['label' => 'value'],
        );
        $owner->attachBehavior(
            $name,
            PlainClass::class,
        );
        $owner->attachBehavior(
            behavior: ProtectedAttachObject::class,
            name: 'named',
        );
        $component->attachBehavior(
            'onComponent',
            PlainClass::class,
        );
        $owner->attachBehavior(
            'multiLine',
            NotAttachableObject::class,
        );
        $owner->attachBehaviors([
            'listed' => PlainClass::class,
            'listedValid' => AttachableBehavior::class,
            ['__class' => NotAttachableObject::class],
        ]);
        $owner->attachBehaviors(self::DEFINITIONS);
        $owner->attachBehaviors(
            [
                '7' => PlainClass::class,
                NotAttachableObject::class,
            ],
        );
    }

    /**
     * @param array<string, string> $definitions
     * @param list<string> $arguments
     */
    public function standAside(
        BehaviorOwner $owner,
        OverridingAttachOwner $overriding,
        object $unknown,
        string $class,
        array $definitions,
        array $arguments,
    ): void {
        $owner->attachBehavior(
            'factory',
            'factoryId',
        );
        $owner->attachBehavior(
            'dynamic',
            $class,
        );
        $owner->attachBehavior(...$arguments);
        $owner->attachBehaviors($definitions);
        $owner->attachBehavior(
            'closure',
            static fn(): string => NotAttachableObject::class,
        );
        $overriding->attachBehavior(
            'overridden',
            NotAttachableObject::class,
        );
        $overriding->attachBehaviors(['overridden' => NotAttachableObject::class]);
        $unknown->attachBehavior(
            'unknownReceiver',
            NotAttachableObject::class,
        );

        $plainClass = new PlainClass();

        $plainClass->attachBehavior(
            'notComponent',
            NotAttachableObject::class,
        );
    }

    public function valid(BehaviorOwner $owner): void
    {
        $owner->attachBehavior(
            'attachable',
            AttachableBehavior::class,
        );
        $owner->attachBehavior(
            'staticAttach',
            StaticAttachObject::class,
        );
        $owner->attachBehavior(
            'staticAttachOnly',
            ['class' => StaticAttachOnly::class],
        );
        $owner->attachBehavior(
            'containerId',
            'attachableBehaviorId',
        );
        $owner->attachBehavior(
            'instance',
            new AttachableBehavior(),
        );
        $owner->attachBehaviors(
            [
                'attachable' => AttachableBehavior::class,
                'static' => StaticAttachObject::class,
            ],
        );
    }
}
