<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

/**
 * Constant configuration parts that the constant-known option type fixtures read from another class.
 */
final class TypedOptions
{
    public const array BASE = ['class' => TypedTarget::class, 'count' => 1];
    public const array CONFIG = ['class' => TypedTarget::class, 'count' => 'abc'];
    public const string KEY = 'count';
    public const TypedSuit SUIT = TypedSuit::Hearts;
    public const array VALID_CONFIG = ['class' => TypedTarget::class, 'count' => '5', 'tags' => ['a']];
}
