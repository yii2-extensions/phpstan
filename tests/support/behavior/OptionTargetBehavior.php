<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

use yii\base\Behavior;

/**
 * Behavior that the container substitutes for {@see OptionRedirectedBehavior}.
 */
class OptionTargetBehavior extends Behavior
{
    public mixed $onlyOnTarget = null;
    public int $targetCount = 0;
}
