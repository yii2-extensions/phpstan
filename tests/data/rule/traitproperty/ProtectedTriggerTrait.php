<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Declares a protected `$trigger`, which a subclass of the class that uses it may redeclare as public.
 */
trait ProtectedTriggerTrait
{
    protected mixed $trigger = null;
}
