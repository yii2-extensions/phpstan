<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

/**
 * Constant configuration parts that the constant-known `Yii::createObject()` fixtures read from another class.
 */
final class Options
{
    public const array BASE = ['class' => Target::class, 'colour' => 'base'];
    public const array CHILD_CLASS = ['class' => ChildTarget::class];
    public const string CLASS_KEY = 'class';
    public const string COMPONENT = ComponentMembersTarget::class;
    public const string COMPONENT_BEHAVIOR = ComponentBehavior::class;
    public const string COMPONENT_BEHAVIORS = ComponentBehaviorsTarget::class;
    public const array COMPONENT_CONFIG = ['class' => ComponentTarget::class, 'colour' => 'component'];
    public const array CONFIG = ['class' => Target::class, 'colour' => 'red'];
    public const array CONSTRUCTOR_CONFIG = ['class' => Target::class, '__construct()' => [[], 'x'], 'colour' => 'x'];
    public const string EMPTY_CLASS = '';
    public const string FACTORY_TARGET = FactoryTarget::class;
    public const string FACTORY_TARGET_WITH_BACKSLASH = '\\' . FactoryTarget::class;
    public const array LIST = ['first'];
    public const array NO_CLASS_CONFIG = ['colour' => 'no class'];
    public const array NO_PARAMS = [];
    public const array NULL_CLASS_CONFIG = ['__class' => null, 'class' => Target::class, 'colour' => 'null __class'];
    public const array PARAMS = [[], 'second'];
    public const string TARGET = Target::class;
    public const array TARGET_CLASS = ['class' => Target::class];
    public const string UNKNOWN = 'colour';
    public const string VALID = 'name';
    public const array VALID_CONFIG = ['class' => Target::class, 'name' => 'public', 'label' => 'setter'];
}
