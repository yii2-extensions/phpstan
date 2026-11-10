<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

use yii\base\Behavior;

/**
 * Behavior whose container definition sets an option.
 */
class OptionDefaultsBehavior extends Behavior
{
    public mixed $preset = null;
}
