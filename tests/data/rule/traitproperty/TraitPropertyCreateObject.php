<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

use Yii;

/**
 * `Yii::createObject()` calls whose options, or the options of their container definitions, target properties that a
 * trait redeclares, and their controls.
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

    public function definitionCount(): object
    {
        return Yii::createObject(['class' => 'traitproperty.count', 'count' => [1, 2]]);
    }

    public function definitionCountTrait(): object
    {
        return Yii::createObject(['class' => 'traitproperty.countTrait', 'count' => [1, 2]]);
    }

    public function definitionInheritedPaintTrigger(): object
    {
        return Yii::createObject(['class' => 'traitproperty.inheritedPaintTrigger', 'colour' => 'blue']);
    }

    public function definitionNestedPaintTrigger(): object
    {
        return Yii::createObject(['class' => 'traitproperty.nestedPaintTrigger', 'colour' => 'blue']);
    }

    public function definitionPaintTrigger(): object
    {
        return Yii::createObject(['class' => 'traitproperty.paintTrigger', 'colour' => 'blue']);
    }

    public function definitionPublicTrigger(): object
    {
        return Yii::createObject(['class' => 'traitproperty.publicTrigger', 'colour' => 'blue']);
    }

    public function definitionShadowedPaintTrigger(): object
    {
        return Yii::createObject(['class' => 'traitproperty.shadowedPaintTrigger', 'colour' => 'blue']);
    }

    public function definitionTrigger(): object
    {
        return Yii::createObject(['class' => 'traitproperty.trigger', 'colour' => 'blue']);
    }

    public function definitionTriggerTrait(): object
    {
        return Yii::createObject(['class' => 'traitproperty.triggerTrait', 'colour' => 'blue']);
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

    public function publicCount(): object
    {
        return Yii::createObject(['class' => PublicCountObject::class, 'count' => [1, 2]]);
    }

    public function publicSecret(): object
    {
        return Yii::createObject(['class' => PublicSecretObject::class, 'secret' => 'open']);
    }

    public function publicTrigger(): object
    {
        return Yii::createObject(['class' => PublicTriggerComponent::class, 'trigger' => 1, 'colour' => 'blue']);
    }

    public function secret(): object
    {
        return Yii::createObject(['class' => SecretObject::class, 'secret' => 'open']);
    }

    public function shadowedPaintTrigger(): object
    {
        return Yii::createObject(['class' => ShadowedPaintTriggerComponent::class, 'trigger' => 1, 'colour' => 'blue']);
    }

    public function shadowedWideCount(): object
    {
        return Yii::createObject(['class' => ShadowedWideCountObject::class, 'count' => [1, 2]]);
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
