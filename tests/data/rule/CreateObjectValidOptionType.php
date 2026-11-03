<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use ArrayIterator;
use Yii;
use yii2\extensions\phpstan\tests\support\createobject\{
    TypedChildTarget,
    TypedOptions,
    TypedSuit,
    TypedTarget,
    TypedValue,
};

/**
 * `Yii::createObject()` option values that the created class accepts, directly or through weak type coercion.
 */
final class CreateObjectValidOptionType
{
    public function anyStringForBool(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'enabled' => 'abc', 'flag' => '']);
    }

    public function arrayForProtectedPropertySetter(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'guardedCount' => [1]]);
    }

    public function arrayForStaticPropertySetter(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'shared' => [1]]);
    }

    public function arrayForWidenedSetterOfChild(): object
    {
        return Yii::createObject(['class' => TypedChildTarget::class, 'limitTo' => [1]]);
    }

    public function boolForInt(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'count' => true, 'ratio' => false]);
    }

    public function callableForSetters(): object
    {
        return Yii::createObject(
            ['class' => TypedTarget::class, 'handler' => 'strlen', 'factory' => static fn(): int => 1],
        );
    }

    public function constantConfiguration(): object
    {
        return Yii::createObject(TypedOptions::VALID_CONFIG);
    }

    public function enumCase(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'suit' => TypedSuit::Hearts]);
    }

    public function floatForInt(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'count' => 5.0, 'limit' => 2.0]);
    }

    public function intForFloatAndString(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'ratio' => 1, 'title' => 5, 'label' => 5]);
    }

    public function intForPublicPropertyBeforeSetter(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'both' => 5]);
    }

    public function iterables(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'rows' => new ArrayIterator([]), 'items' => [1]]);
    }

    public function nullForNullable(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'limit' => null, 'label' => null, 'value' => null]);
    }

    public function numericStringsForNumbers(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'count' => ' 5', 'ratio' => '1e3', 'limit' => '5 ']);
    }

    public function phpDocNarrowerThanNativeType(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'documented' => '', 'size' => 0]);
    }

    public function stringableForString(): object
    {
        return Yii::createObject(
            ['class' => TypedTarget::class, 'title' => new TypedValue(), 'text' => new TypedValue()],
        );
    }

    public function untypedAndParameterless(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'untyped' => [], 'anything' => [], 'nothing' => []]);
    }

    public function variadicAndTraitSetters(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'values' => 1, 'traitLevel' => '2']);
    }
}
