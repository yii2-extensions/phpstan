<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

/**
 * Invocable object that `Yii::createObject()` calls, returning a behavior.
 */
final class InvokableFactory
{
    public function __invoke(): AttachableBehavior
    {
        return new AttachableBehavior();
    }
}
