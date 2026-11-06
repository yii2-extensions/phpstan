<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Uses {@see WideCountTrait}, so that the set hook comes from a nested trait.
 */
trait NestedWideCountTrait
{
    use WideCountTrait;
}
