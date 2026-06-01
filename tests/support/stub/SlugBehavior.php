<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * Stub behavior exposing a `string` `slug` property for behavior property resolution tests.
 *
 * @template T of ActiveRecord
 * @extends Behavior<T>
 *
 * @property string $slug
 */
final class SlugBehavior extends Behavior {}
