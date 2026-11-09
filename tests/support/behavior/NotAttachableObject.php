<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

use yii\base\BaseObject;

/**
 * `BaseObject` subclass without `attach()`, whose inherited `__call()` always throws.
 */
class NotAttachableObject extends BaseObject {}
