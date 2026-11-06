<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Uses {@see PaintTriggerTrait}, so that the set hook comes from a nested trait.
 */
trait NestedPaintTriggerTrait
{
    use PaintTriggerTrait;
}
