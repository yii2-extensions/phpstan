<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

use yii\base\Behavior;

/**
 * Behavior whose property and setter {@see OptionBehavior} inherits.
 */
class OptionParentBehavior extends Behavior
{
    public mixed $inherited = null;

    public function setParentSetter(mixed $value): void {}
}
