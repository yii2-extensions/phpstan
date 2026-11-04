<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use yii\di\Instance;
use yii2\extensions\phpstan\tests\support\createobject\{
    AliasTarget,
    ChildTarget,
    ComponentBehavior,
    ComponentTarget,
    Target,
    TypedTarget,
    TypedValue,
};

/**
 * Direct `new` constructions whose configuration the constructed class assigns, directly or through weak coercion.
 */
final class NewObjectValidOption
{
    private const array CONFIG = ['count' => '5', 'title' => 'constant'];

    public function boundClass(): object
    {
        return new AliasTarget(['name' => 'value']);
    }

    public function classKeyThroughSetter(): object
    {
        return new TypedTarget(['class' => 5]);
    }

    public function componentEventAndBehavior(): object
    {
        return new ComponentTarget(
            [
                'on refresh' => static function (): void {},
                'as colouring' => ComponentBehavior::class,
                'colour' => 'red',
            ],
        );
    }

    public function constantConfiguration(): object
    {
        return new TypedTarget(self::CONFIG);
    }

    public function emptyConfiguration(): object
    {
        return new Target([]);
    }

    public function inheritedMembers(): object
    {
        return new ChildTarget(
            [
                'name' => 'value',
                'label' => 'value',
                'shade' => 'dark',
                'tagged' => 'value',
            ]
        );
    }

    public function instanceForMixedProperty(): object
    {
        return new TypedTarget(['untyped' => $this->reference()]);
    }

    public function instanceForMixedSetter(): object
    {
        return new TypedTarget(['anything' => $this->reference()]);
    }

    public function namedConfig(): object
    {
        return new Target(config: ['name' => 'value']);
    }

    public function noArgument(): object
    {
        return new Target();
    }

    public function propertiesAndSetters(): object
    {
        return new Target(
            [
                'name' => 'value',
                'label' => 'value',
                'guarded' => 'value',
                'LABEL' => 'value',
            ],
        );
    }

    public function weakCoercionForProperties(): object
    {
        return new TypedTarget(
            [
                'count' => '5',
                'title' => 5,
                'enabled' => 'yes',
                'ratio' => '1.5',
            ],
        );
    }

    public function weakCoercionForSetters(): object
    {
        return new TypedTarget(
            [
                'limitTo' => '7',
                'text' => 5,
                'flag' => 0,
            ],
        );
    }

    private function reference(): Instance
    {
        return Instance::of(TypedValue::class);
    }
}
