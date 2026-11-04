<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use yii\di\Instance;
use yii2\extensions\phpstan\tests\support\createobject\{
    ComponentMembersTarget,
    TypedChildTarget,
    TypedPlain,
    TypedTarget,
    TypedValue,
};

/**
 * Direct `new` constructions whose configuration assigns a value that the property or setter never accepts.
 */
final class NewObjectInvalidOptionType
{
    private const array CONFIG = ['title' => 'constant', 'count' => 'all'];

    public function arrayForStringSetter(): object
    {
        return new TypedTarget(['text' => []]);
    }

    public function classKeyForIntSetter(): object
    {
        return new TypedTarget(['class' => TypedTarget::class]);
    }

    public function componentProperty(): object
    {
        return new ComponentMembersTarget(['count' => 'all']);
    }

    public function constantConfiguration(): object
    {
        return new TypedTarget(self::CONFIG);
    }

    public function inheritedProperty(): object
    {
        return new TypedChildTarget(['count' => 'all']);
    }

    public function instanceForClassProperty(): object
    {
        return new TypedTarget(['value' => $this->reference()]);
    }

    public function namedConfig(): object
    {
        return new TypedTarget(config: ['count' => []]);
    }

    public function nonNumericStringForIntProperty(): object
    {
        return new TypedTarget(['count' => 'all']);
    }

    public function nullForStringProperty(): object
    {
        return new TypedTarget(['title' => null]);
    }

    public function objectForIntProperty(): object
    {
        return new TypedTarget(['count' => new TypedPlain()]);
    }

    private function reference(): Instance
    {
        return Instance::of(TypedValue::class);
    }
}
