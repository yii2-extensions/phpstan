<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

use yii\base\BaseObject;

/**
 * `BaseObject` subclass with protected static `attach()`, so that Yii's call falls through to the throwing `__call()`.
 */
class ProtectedStaticAttachObject extends BaseObject
{
    protected static function attach(object $owner): void {}
}
