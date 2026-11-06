<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Direct `new` constructions whose `$config` options target properties that a trait redeclares, and their controls.
 */
final class TraitPropertyNewObject
{
    public function count(): object
    {
        return new CountObject(['count' => [1, 2]]);
    }

    public function countTrait(): object
    {
        return new CountTraitObject(['count' => [1, 2]]);
    }

    public function inheritedWideCount(): object
    {
        return new InheritedWideCountObject(['count' => [1, 2]]);
    }

    public function nestedWideCount(): object
    {
        return new NestedWideCountObject(['count' => [1, 2]]);
    }

    public function paintTrigger(): object
    {
        return new PaintTriggerComponent(['trigger' => 1, 'colour' => 'blue']);
    }

    public function publicCount(): object
    {
        return new PublicCountObject(['count' => [1, 2]]);
    }

    public function publicSecret(): object
    {
        return new PublicSecretObject(['secret' => 'open']);
    }

    public function publicTrigger(): object
    {
        return new PublicTriggerComponent(['trigger' => 1, 'colour' => 'blue']);
    }

    public function secret(): object
    {
        return new SecretObject(['secret' => 'open']);
    }

    public function shadowedPaintTrigger(): object
    {
        return new ShadowedPaintTriggerComponent(['trigger' => 1, 'colour' => 'blue']);
    }

    public function shadowedWideCount(): object
    {
        return new ShadowedWideCountObject(['count' => [1, 2]]);
    }

    public function trigger(): object
    {
        return new TriggerComponent(['trigger' => 1, 'colour' => 'blue']);
    }

    public function wideCount(): object
    {
        return new WideCountObject(['count' => [1, 2]]);
    }
}
