<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

use Yii;

/**
 * `Yii::createObject()` calls whose options target properties that a trait redeclares, and their controls.
 */
final class TraitPropertyCreateObject
{
    public function count(): object
    {
        return Yii::createObject(['class' => CountObject::class, 'count' => [1, 2]]);
    }

    public function countTrait(): object
    {
        return Yii::createObject(['class' => CountTraitObject::class, 'count' => [1, 2]]);
    }

    public function inheritedWideCount(): object
    {
        return Yii::createObject(['class' => InheritedWideCountObject::class, 'count' => [1, 2]]);
    }

    public function nestedWideCount(): object
    {
        return Yii::createObject(['class' => NestedWideCountObject::class, 'count' => [1, 2]]);
    }

    public function paintTrigger(): object
    {
        return Yii::createObject(['class' => PaintTriggerComponent::class, 'trigger' => 1, 'colour' => 'blue']);
    }

    public function publicSecret(): object
    {
        return Yii::createObject(['class' => PublicSecretObject::class, 'secret' => 'open']);
    }

    public function secret(): object
    {
        return Yii::createObject(['class' => SecretObject::class, 'secret' => 'open']);
    }

    public function trigger(): object
    {
        return Yii::createObject(['class' => TriggerComponent::class, 'trigger' => 1, 'colour' => 'blue']);
    }

    public function wideCount(): object
    {
        return Yii::createObject(['class' => WideCountObject::class, 'count' => [1, 2]]);
    }
}
