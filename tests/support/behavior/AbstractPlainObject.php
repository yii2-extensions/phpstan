<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

use yii\base\BaseObject;

/**
 * Abstract `BaseObject` subclass without `attach()`, which the container can't instantiate.
 */
abstract class AbstractPlainObject extends BaseObject {}
