<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use yii\base\Component;

/**
 * Component that overrides `__set()`, so that its `as` options don't reach `Component::__set()`.
 */
final class SetOverridingOwner extends Component
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
