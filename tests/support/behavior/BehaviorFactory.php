<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

/**
 * Static factory that `Yii::createObject()` invokes for a callable array definition.
 */
final class BehaviorFactory
{
    public static function create(): AttachableBehavior
    {
        return new AttachableBehavior();
    }
}
