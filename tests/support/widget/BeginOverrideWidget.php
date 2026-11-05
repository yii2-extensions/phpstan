<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\widget;

/**
 * Subclass of {@see TargetWidget} that overrides `begin()` and forwards its configuration.
 */
class BeginOverrideWidget extends TargetWidget
{
    /**
     * @param array<mixed> $config
     */
    public static function begin($config = []): static
    {
        return parent::begin($config);
    }
}
