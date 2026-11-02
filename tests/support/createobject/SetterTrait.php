<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

/**
 * Setter provided to a configurable target through a trait.
 */
trait SetterTrait
{
    public string $tag = '';

    public function setTagged(string $value): void
    {
        $this->tag = $value;
    }
}
