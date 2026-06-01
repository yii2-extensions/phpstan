<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * Stub behavior exposing nested sets `depth`, `lft`, and `rgt` integer properties for behavior resolution tests.
 *
 * @template T of ActiveRecord
 * @extends Behavior<T>
 *
 * @property int $depth
 * @property int $lft
 * @property int $rgt
 */
final class NestedSetsBehavior extends Behavior {}
