<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use stdClass;
use Yii;
use yii\BaseYii;
use yii2\extensions\phpstan\tests\support\createobject\{
    TypedChildTarget,
    TypedOptions,
    TypedPlain,
    TypedSuit,
    TypedTarget,
    TypedValue,
};

/**
 * `Yii::createObject()` option values that the property or setter Yii assigns them to never accepts, one per method.
 */
final class CreateObjectInvalidOptionType
{
    public function arrayForBoolProperty(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'enabled' => []]);
    }

    public function arrayForIntOrStringProperty(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'key' => []]);
    }

    public function arrayForIntProperty(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'count' => []]);
    }

    public function arrayForNullableStringSetter(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'label' => []]);
    }

    public function arrayForPublicPropertyBeforeSetter(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'both' => []]);
    }

    public function arrayForStringProperty(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'title' => []]);
    }

    public function arrayForStringSetter(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'text' => []]);
    }

    public function constantKey(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, TypedOptions::KEY => 'abc']);
    }

    public function enumCaseForIntProperty(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'count' => TypedSuit::Hearts]);
    }

    public function enumConstantForIntProperty(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'count' => TypedOptions::SUIT]);
    }

    public function finalClassForInterfaceProperty(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'contract' => new TypedValue()]);
    }

    public function inheritedProperty(): object
    {
        return Yii::createObject(['class' => TypedChildTarget::class, 'count' => 'abc']);
    }

    public function lastDuplicateValue(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'count' => 1, 'count' => 'abc']);
    }

    public function leadingNumericStringForFloatProperty(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'ratio' => '1abc']);
    }

    public function multilineKey(): object
    {
        return Yii::createObject(
            [
                'class' => TypedTarget::class,
                'count'
                    => 'abc',
            ],
        );
    }

    public function namedArguments(): object
    {
        return BaseYii::createObject(params: [], type: ['__class' => TypedTarget::class, 'count' => 'abc']);
    }

    public function nonNumericStringForIntProperty(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'count' => 'abc']);
    }

    public function nonNumericStringForNullableIntProperty(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'limit' => 'abc']);
    }

    public function nullForIntProperty(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'count' => null]);
    }

    public function nullForStringProperty(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'title' => null]);
    }

    public function objectForBoolSetter(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'flag' => new TypedPlain()]);
    }

    public function objectForFinalClassProperty(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'value' => new stdClass()]);
    }

    public function objectForIntProperty(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'count' => new TypedPlain()]);
    }

    public function ownPropertyOfChild(): object
    {
        return Yii::createObject(['class' => TypedChildTarget::class, 'weight' => 'abc']);
    }

    public function protectedPropertyUsesSetter(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'guardedCount' => 'abc']);
    }

    public function staticPropertyUsesSetter(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'shared' => 'abc']);
    }

    public function staticSetter(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'defaultCount' => 'abc']);
    }

    public function stringForArrayProperty(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'items' => 'abc']);
    }

    public function stringForArraySetter(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'tags' => 'abc']);
    }

    public function stringForClosureSetter(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'factory' => 'strlen']);
    }

    public function stringForEnumProperty(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'suit' => 'H']);
    }

    public function stringForIntSetter(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'limitTo' => 'abc']);
    }

    public function stringForIterableProperty(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'rows' => 'abc']);
    }

    public function stringForSetterInheritedByChild(): object
    {
        return Yii::createObject(['class' => TypedChildTarget::class, 'traitLevel' => 'abc']);
    }

    public function stringForTraitSetter(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'traitLevel' => 'abc']);
    }

    public function stringForTwoParameterSetter(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'pair' => 'abc']);
    }

    public function stringForVariadicSetter(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'values' => 'abc']);
    }

    public function stringForWidenedSetterOfChild(): object
    {
        return Yii::createObject(['class' => TypedChildTarget::class, 'limitTo' => 'abc']);
    }

    public function twoInvalidOptions(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'count' => '', 'title' => []]);
    }
}
