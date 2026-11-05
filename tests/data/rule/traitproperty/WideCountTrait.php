<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

use function count;
use function is_array;

/**
 * Redeclares an inherited `int $count` with a set hook that also accepts an array and stores its size.
 */
trait WideCountTrait
{
    public int $count = 0 {
        set(array|int $value) {
            $this->count = is_array($value) ? count($value) : $value;
        }
    }
}
