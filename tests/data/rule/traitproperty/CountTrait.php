<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Redeclares an inherited `int $count` without a hook, as PHP 8.3 already allows.
 */
trait CountTrait
{
    public int $count = 0;
}
