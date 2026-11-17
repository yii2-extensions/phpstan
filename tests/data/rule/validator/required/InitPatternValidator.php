<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\required;

use yii\validators\RegularExpressionValidator;

/**
 * Regular expression validator subclass whose `init()` supplies a missing pattern.
 */
final class InitPatternValidator extends RegularExpressionValidator
{
    public function init(): void
    {
        $this->pattern ??= '/^[a-z]+$/';

        parent::init();
    }
}
