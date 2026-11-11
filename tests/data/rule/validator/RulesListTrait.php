<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

/**
 * Trait whose `rules()` returns an incomplete rule; the using class is not known while the trait is analysed.
 */
trait RulesListTrait
{
    public function rules(): array
    {
        return [['name']];
    }
}
