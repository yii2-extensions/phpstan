<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use yii\base\Model;
use yii\validators\RequiredValidator;

/**
 * Model that overrides `createValidators()` through a trait, returning an invalid validator type.
 */
final class TraitCreateValidatorsDefinitionModel extends Model
{
    use CreateValidatorsOverrideTrait;

    public function rules(): array
    {
        return [['name', ['class' => RequiredValidator::class]]];
    }
}
