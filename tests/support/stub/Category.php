<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveRecord;

/**
 * Stub ActiveRecord model with a nullable parent relationship for hierarchical category tests.
 *
 * @property int $id
 * @property string $name
 * @property int|null $parent_id
 */
class Category extends ActiveRecord
{
    public function rules(): array
    {
        return [
            [['id', 'parent_id'], 'integer'],
            [['name'], 'string'],
            [['parent_id'], 'default', 'value' => null],
        ];
    }

    public static function tableName(): string
    {
        return 'categories';
    }
}
