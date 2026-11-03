<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

/**
 * Natively typed setter provided to a configurable target through a trait.
 */
trait TypedSetterTrait
{
    public int $level = 0;

    public function setTraitLevel(int $level): void
    {
        $this->level = $level;
    }
}
