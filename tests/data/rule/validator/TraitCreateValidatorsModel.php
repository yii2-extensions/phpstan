<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use yii\base\Model;

/**
 * Model that overrides `createValidators()` through a trait.
 */
final class TraitCreateValidatorsModel extends Model
{
    use CreateValidatorsOverrideTrait;

    public function rules(): array
    {
        return [['name']];
    }
}
