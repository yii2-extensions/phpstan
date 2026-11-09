<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

use yii\base\Component;

/**
 * Component without behaviors of its own, used as receiver of attach calls.
 */
class BehaviorOwner extends Component {}
