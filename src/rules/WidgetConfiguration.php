<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use yii\base\Widget;

/**
 * Represents the configuration of a {@see Widget::widget()} or {@see Widget::begin()} call whose class and assignment
 * path are proven.
 *
 * {@see WidgetConfigurationResolver} for the conditions under which a configuration is resolved.
 */
final readonly class WidgetConfiguration
{
    /**
     * @param string $method Name of the called method as {@see Widget} declares it: `widget` or `begin`.
     * @param CreateObjectConfiguration $configuration Configuration that `Yii::createObject()` receives from the
     * method: the called class as PHPStan displays it, the widget class Yii creates and the options it assigns.
     */
    public function __construct(
        public string $method,
        public CreateObjectConfiguration $configuration,
    ) {}
}
