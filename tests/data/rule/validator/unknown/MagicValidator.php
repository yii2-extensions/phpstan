<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\unknown;

use yii\validators\StringValidator;

/**
 * Validator whose own `__set()` accepts every option name.
 */
final class MagicValidator extends StringValidator
{
    public function __set($name, $value): void {}
}
