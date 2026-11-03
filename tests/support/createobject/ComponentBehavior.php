<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use yii\base\{Behavior, Component};

/**
 * Behavior whose public `colour` property receives the option of the same name from its owner's configuration.
 *
 * @extends Behavior<Component>
 */
final class ComponentBehavior extends Behavior
{
    public string $colour = '';
}
