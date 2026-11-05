<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\widget;

/**
 * Subclass of {@see TargetWidget} that overrides `widget()` and forwards its configuration.
 */
class WidgetOverrideWidget extends TargetWidget
{
    /**
     * @param array<mixed> $config
     */
    public static function widget($config = []): string
    {
        return parent::widget($config);
    }
}
