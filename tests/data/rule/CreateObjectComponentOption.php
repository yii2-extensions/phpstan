<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use Yii;
use yii\base\Component;
use yii2\extensions\phpstan\tests\support\createobject\{
    ComponentAbstractTarget,
    ComponentBehavior,
    ComponentBehaviorsTarget,
    ComponentBoundTarget,
    ComponentContractTarget,
    ComponentCustomConstructorTarget,
    ComponentCustomSetterTarget,
    ComponentEnsureBehaviorsTarget,
    ComponentEventTarget,
    ComponentInheritedBehaviorsTarget,
    ComponentMappedChildTarget,
    ComponentMappedTarget,
    ComponentMembersTarget,
    ComponentTarget,
    Options,
    Target,
};

/**
 * `Yii::createObject()` calls of `yii\base\Component` subclasses for both option rules, one per method.
 */
final class CreateObjectComponentOption
{
    public function abstractClass(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentAbstractTarget::class,
                'colour' => 'red',
                'count' => 'abc',
            ]
        );
    }

    public function acceptedValues(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMembersTarget::class,
                'name' => 'public',
                'count' => '7',
                'limit' => '5',
                'guarded' => 5,
            ],
        );
    }

    public function arrayForStringProperty(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentTarget::class,
                'name' => [],
            ]
        );
    }

    public function behaviorAfterOption(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMembersTarget::class,
                'colour' => 'red',
                'as colour' => ComponentBehavior::class,
            ],
        );
    }

    public function behaviorBeforeInvalidValue(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMembersTarget::class,
                'as colour' => ComponentBehavior::class,
                'count' => 'abc',
            ],
        );
    }

    public function behaviorBeforeOption(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMembersTarget::class,
                'as colour' => ComponentBehavior::class,
                'colour' => 'red',
            ],
        );
    }

    public function behaviorsOverride(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentBehaviorsTarget::class,
                'colour' => 'red',
            ]
        );
    }

    public function behaviorsOverrideInvalidBehaviorValue(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentBehaviorsTarget::class,
                'colour' => [],
            ]
        );
    }

    public function behaviorsOverrideInvalidValue(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentBehaviorsTarget::class,
                'count' => 'abc',
            ]
        );
    }

    public function boundClass(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentBoundTarget::class,
                'colour' => 'red',
                'count' => 'abc',
            ]
        );
    }

    public function componentClass(): object
    {
        return Yii::createObject(
            [
                'class' => Component::class,
                'colour' => 'red',
            ]
        );
    }

    public function constantConfiguration(): object
    {
        return Yii::createObject(Options::COMPONENT_CONFIG);
    }

    public function customConstructor(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentCustomConstructorTarget::class,
                'alias' => 'consumed',
                'count' => 'abc',
            ],
        );
    }

    public function customMagicSetter(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentCustomSetterTarget::class,
                'colour' => 'red',
            ]
        );
    }

    public function ensureBehaviorsOverride(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentEnsureBehaviorsTarget::class,
                'colour' => 'red',
            ]
        );
    }

    public function event(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMembersTarget::class,
                'on change' => static function (): void {},
            ],
        );
    }

    public function eventBeforeOption(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMembersTarget::class,
                'on change' => static function (): void {},
                'colour' => 'red',
            ],
        );
    }

    public function eventOverrideAfterOption(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentEventTarget::class,
                'colour' => 'red',
                'on change' => static function (): void {},
            ],
        );
    }

    public function eventOverrideBeforeOption(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentEventTarget::class,
                'on change' => static function (): void {},
                'colour' => 'red',
            ],
        );
    }

    public function finalClass(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentTarget::class,
                'colour' => 'component',
            ]
        );
    }

    public function getterOnly(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMembersTarget::class,
                'title' => 'read-only',
            ]
        );
    }

    public function inheritedBehaviorsOverride(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentInheritedBehaviorsTarget::class,
                'colour' => 'red',
            ]
        );
    }

    public function lastClassValue(): object
    {
        return Yii::createObject(
            [
                'class' => Target::class,
                'class' => ComponentTarget::class,
                'colour' => 'last class',
            ],
        );
    }

    public function mappedAncestor(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMappedChildTarget::class,
                'colour' => 'red',
            ]
        );
    }

    public function mappedClass(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMappedTarget::class,
                'colour' => 'red',
            ]
        );
    }

    public function mappedInterface(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentContractTarget::class,
                'colour' => 'red',
            ]
        );
    }

    public function optionBeforeSetterAttachment(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMembersTarget::class,
                'colour' => 'red',
                'attach' => true,
            ],
        );
    }

    public function privateProperty(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMembersTarget::class,
                'secret' => 'value',
            ]
        );
    }

    public function privateSetterBeforeOption(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMembersTarget::class,
                'hidden' => 'value',
                'colour' => 'red',
            ],
        );
    }

    public function protectedPropertyUsesSetter(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMembersTarget::class,
                'guarded' => 'abc',
            ]
        );
    }

    public function protectedPropertyWithoutSetter(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMembersTarget::class,
                'internal' => 'value',
            ]
        );
    }

    public function setterAttachmentBeforeOption(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMembersTarget::class,
                'attach' => true,
                'colour' => 'red',
            ],
        );
    }

    public function setterBeforeOption(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMembersTarget::class,
                'limit' => 5,
                'colour' => 'red',
            ],
        );
    }

    public function staticProperty(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMembersTarget::class,
                'shared' => 1,
            ]
        );
    }

    public function stringForIntProperty(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMembersTarget::class,
                'count' => 'abc',
            ]
        );
    }

    public function stringForIntSetter(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMembersTarget::class,
                'limit' => 'abc',
            ]
        );
    }

    public function twoUnknownOptions(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMembersTarget::class,
                'colour' => 'red',
                'size' => 'large',
            ],
        );
    }

    public function unknownAfterPublicProperty(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMembersTarget::class,
                'name' => 'public',
                'colour' => 'red',
            ],
        );
    }

    public function unknownOption(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMembersTarget::class,
                'colour' => 'red',
            ]
        );
    }

    public function uppercaseBehaviorPrefix(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMembersTarget::class,
                'As colour' => ComponentBehavior::class,
            ]
        );
    }

    public function uppercaseEventPrefix(): object
    {
        return Yii::createObject(
            [
                'class' => ComponentMembersTarget::class,
                'On change' => static function (): void {},
            ]
        );
    }
}
