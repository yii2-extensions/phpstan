<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use yii\base\Model;

/**
 * Model whose `rules()` returns an empty list, which declares no rule.
 */
final class EmptyRulesModel extends Model
{
    public function rules(): array
    {
        return [];
    }
}
