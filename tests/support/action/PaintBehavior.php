<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\action;

use yii\base\{Behavior, Component};

/**
 * Behavior with a `colour` property, attached by the property hooks of the hook actions.
 *
 * @extends Behavior<Component>
 */
final class PaintBehavior extends Behavior
{
    public string $colour = '';
}
