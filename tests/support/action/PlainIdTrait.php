<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\action;

/**
 * Redeclares `Action::$id` identically, without a hook, as PHP 8.3 already allows.
 */
trait PlainIdTrait
{
    public $id;
}
