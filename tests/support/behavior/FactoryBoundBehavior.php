<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

use yii\base\Behavior;

/**
 * Behavior class that the container builds through a factory returning {@see NotAttachableObject}.
 */
class FactoryBoundBehavior extends Behavior {}
