<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Declares a protected `int $count`, which a subclass of the class that uses it may redeclare as public.
 */
trait ProtectedCountTrait
{
    protected int $count = 0;
}
