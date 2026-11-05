<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\widget;

/**
 * Provides a `widget()` implementation that forwards the configuration to the parent class.
 */
trait WidgetTrait
{
    /**
     * @param array<mixed> $config
     */
    public static function widget($config = []): string
    {
        return parent::widget($config);
    }
}
