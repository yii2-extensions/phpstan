<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveRecord;

/**
 * Stub ActiveRecord model attaching {@see NestedSetsBehavior} for behavior property resolution tests.
 *
 * @property int $depth
 * @property int $lft
 * @property int $rgt
 */
final class NestedSetsModel extends ActiveRecord
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
        return 'nested_sets_test';
    }
}
