<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\validator;

use ArrayObject;
use yii\base\Model;
use yii\validators\Validator;

/**
 * Model base class that overrides `createValidators()` and never reads `rules()`.
 */
abstract class CreateValidatorsOverrideBase extends Model
{
    /**
     * @return ArrayObject<int, Validator>
     */
    public function createValidators(): ArrayObject
    {
        return new ArrayObject();
    }
}
