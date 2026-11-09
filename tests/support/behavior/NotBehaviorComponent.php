<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

use yii\base\Component;

/**
 * `Component` subclass that isn't a behavior, whose `__call()` may reach an attached behavior.
 */
class NotBehaviorComponent extends Component {}
