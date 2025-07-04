<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\stub;

use yii\db\ActiveRecord;

/**
 * Representing hierarchical category data for testing Active Record scenarios.
 *
 * Provides a simple Active Record implementation with integer and string properties, supporting nullable parent
 * relationships for hierarchical data structures.
 *
 * This class is used in type inference and static analysis tests to validate property access, rule definitions, and
 * table mapping in Yii Active Record extensions.
 *
 * Key features.
 * - Explicit table name mapping for database operations.
 * - Integer and string property definitions for category data.
 * - Nullable parent ID for representing category hierarchies.
 * - Validation rules for property types and default values.
 *
 * @property int $id
 * @property string $name
 * @property int|null $parent_id
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
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
