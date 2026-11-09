<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

/**
 * Class outside `BaseObject` with protected `attach()` and no `__call()`.
 */
class ProtectedAttachClass
{
    protected function attach(object $owner): void {}
}
