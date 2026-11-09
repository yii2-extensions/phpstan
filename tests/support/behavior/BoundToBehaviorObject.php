<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

use yii\base\BaseObject;

/**
 * `BaseObject` subclass that isn't a behavior, which the container resolves to {@see AttachableBehavior}.
 */
class BoundToBehaviorObject extends BaseObject {}
