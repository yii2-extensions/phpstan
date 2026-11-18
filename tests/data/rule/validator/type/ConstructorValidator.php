<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\type;

use yii\validators\Validator;

/**
 * Validator whose own constructor ignores the configuration, so Yii never assigns its options.
 */
final class ConstructorValidator extends Validator
{
    public int $limit = 10;

    public function __construct($config = []) {}
}
