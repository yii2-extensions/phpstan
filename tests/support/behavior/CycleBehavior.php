<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

use yii\base\Behavior;

/**
 * Behavior class whose container alias chain leads back to itself, which never returns at runtime.
 */
class CycleBehavior extends Behavior {}
