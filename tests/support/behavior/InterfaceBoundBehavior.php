<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

use yii\base\Behavior;

/**
 * Behavior class that the container resolves to the unbound interface {@see UnboundContract}.
 */
class InterfaceBoundBehavior extends Behavior {}
