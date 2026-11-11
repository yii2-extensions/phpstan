<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use yii\base\Model;

/**
 * Model whose `rules()` returns a literal list with an unpacked constant, which native types prove constant.
 */
final class UnpackedListRulesModel extends Model
{
    private const array BASE = [['name', 'required']];

    public function rules(): array
    {
        return [...self::BASE, [null, 'required']];
    }
}
