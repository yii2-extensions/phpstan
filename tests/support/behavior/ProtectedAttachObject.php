<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

use yii\base\BaseObject;

/**
 * `BaseObject` subclass with protected `attach()`, so that Yii's call falls through to the throwing `__call()`.
 */
class ProtectedAttachObject extends BaseObject
{
    protected function attach(object $owner): void {}
}
