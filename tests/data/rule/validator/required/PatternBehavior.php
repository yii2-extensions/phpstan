<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\required;

use yii\base\{Behavior, Component};
use yii\validators\RegularExpressionValidator;

/**
 * Behavior that sets the pattern of its owner when attached, before the owner's `init()` runs.
 */
final class PatternBehavior extends Behavior
{
    /**
     * @param Component $owner Component the behavior is attached to.
     */
    public function attach($owner): void
    {
        parent::attach($owner);

        if ($owner instanceof RegularExpressionValidator) {
            $owner->pattern = '/^[a-z]+$/';
        }
    }
}
