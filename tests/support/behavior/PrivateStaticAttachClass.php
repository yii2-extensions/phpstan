<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

/**
 * Class outside `BaseObject` with private static `attach()` and no `__call()`.
 */
class PrivateStaticAttachClass
{
    private static function attach(object $owner): void {}
}
