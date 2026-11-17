<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\unknown;

use yii\validators\StringValidator;

/**
 * Validator whose own constructor ignores the configuration, so Yii never assigns its options.
 */
final class ConstructorValidator extends StringValidator
{
    public function __construct($config = []) {}
}
