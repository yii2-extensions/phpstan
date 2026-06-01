<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveRecord;

/**
 * Stub ActiveRecord model declaring `lft` as `string` to conflict with the `int` `lft` of {@see NestedSetsBehavior}.
 *
 * @property string $lft
 */
final class ModelWithConflictingProperty extends ActiveRecord
{
    public function behaviors(): array
    {
        return [
            'nestedSets' => [
                'class' => NestedSetsBehavior::class,
            ],
        ];
    }

    public static function tableName(): string
    {
        return 'conflict_test';
    }
}
