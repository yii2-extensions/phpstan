<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use ArrayObject;
use yii\validators\Validator;

/**
 * Provides a `createValidators()` override that never reads `rules()`.
 */
trait CreateValidatorsOverrideTrait
{
    /**
     * @return ArrayObject<int, Validator>
     */
    public function createValidators(): ArrayObject
    {
        return new ArrayObject();
    }
}
