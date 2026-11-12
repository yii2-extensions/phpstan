<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use yii\base\Model;

/**
 * Model whose `rules()` unpacks a constant list, so the rules are located at the return line.
 */
final class UnpackedInvalidDefinitionListModel extends Model
{
    private const array BASE = [['name', 'required']];

    public function rules(): array
    {
        return [...self::BASE, ['name', new DefinitionInvocable()]];
    }
}
