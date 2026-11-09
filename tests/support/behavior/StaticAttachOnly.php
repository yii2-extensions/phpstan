<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

use yii\base\BaseObject;

/**
 * `BaseObject` subclass with public static `attach()` and no `detach()`: it attaches, and only a later detach fails.
 */
class StaticAttachOnly extends BaseObject
{
    public static function attach(object $owner): void {}
}
