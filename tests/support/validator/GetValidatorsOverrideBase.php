<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\validator;

use ArrayObject;
use yii\base\Model;
use yii\validators\Validator;

/**
 * Model base class that overrides `getValidators()` and never reads `rules()`.
 */
abstract class GetValidatorsOverrideBase extends Model
{
    /**
     * @return ArrayObject<int, Validator>
     */
    public function getValidators(): ArrayObject
    {
        return new ArrayObject();
    }
}
