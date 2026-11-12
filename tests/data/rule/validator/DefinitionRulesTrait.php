<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use yii\validators\RequiredValidator;

/**
 * Trait whose `rules()` returns an invalid validator type; the using class is not known while the trait is analysed.
 */
trait DefinitionRulesTrait
{
    public function rules(): array
    {
        return [['name', ['class' => RequiredValidator::class]]];
    }
}
