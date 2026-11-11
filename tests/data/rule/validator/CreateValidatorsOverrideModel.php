<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use ArrayObject;
use yii\base\Model;

/**
 * Model that overrides `createValidators()`, so that Yii's use of `rules()` isn't proven.
 */
final class CreateValidatorsOverrideModel extends Model
{
    public function createValidators(): ArrayObject
    {
        return new ArrayObject();
    }

    public function rules(): array
    {
        return [['name']];
    }
}
