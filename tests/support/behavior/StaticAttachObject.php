<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

use yii\base\BaseObject;

/**
 * `BaseObject` subclass that declares public static `attach()` and `detach()`, which Yii calls on the instance.
 */
class StaticAttachObject extends BaseObject
{
    public static function attach(object $owner): void {}

    public static function detach(): void {}
}
