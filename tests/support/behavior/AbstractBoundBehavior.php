<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

use yii\base\Behavior;

/**
 * Behavior class that the container resolves to the abstract {@see AbstractPlainObject}, which isn't a behavior.
 */
class AbstractBoundBehavior extends Behavior {}
