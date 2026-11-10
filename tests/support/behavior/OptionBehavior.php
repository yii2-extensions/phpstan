<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

/**
 * Behavior with the members that a configuration option may or may not reach.
 */
class OptionBehavior extends OptionParentBehavior
{
    public mixed $label = null;
    protected mixed $hidden = null;

    public function getReadOnly(): int
    {
        return 1;
    }

    public function setWritable(mixed $value): void {}

    protected function setProtectedSetter(mixed $value): void {}
}
