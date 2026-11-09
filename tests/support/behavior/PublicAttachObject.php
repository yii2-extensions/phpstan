<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

use yii\base\BaseObject;

/**
 * `BaseObject` subclass that isn't a behavior but declares public `attach()` and `detach()`.
 */
class PublicAttachObject extends BaseObject
{
    public function attach(object $owner): void {}

    public function detach(): void {}
}
