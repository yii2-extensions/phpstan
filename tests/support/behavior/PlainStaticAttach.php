<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

/**
 * Class outside `BaseObject` with public static `attach()` and `detach()`.
 */
class PlainStaticAttach
{
    public static function attach(object $owner): void {}

    public static function detach(): void {}
}
