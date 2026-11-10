<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

use yii\base\Behavior;

/**
 * Behavior that overrides `__set()`, so that it accepts every option.
 */
class SetOverrideBehavior extends Behavior
{
    /**
     * Options received by `__set()`, by name.
     *
     * @var array<string, mixed>
     */
    public array $options = [];

    public function __set($name, $value): void
    {
        $this->options[$name] = $value;
    }
}
