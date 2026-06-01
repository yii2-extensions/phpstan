<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveRecord;

/**
 * Stub ActiveRecord model combining {@see NestedSetsBehavior} and {@see SlugBehavior} for property aggregation tests.
 *
 * @property int $depth
 * @property int $lft
 * @property int $rgt
 * @property string $slug
 */
final class ModelWithMultipleBehaviors extends ActiveRecord
{
    public function behaviors(): array
    {
        return [
            'nestedSets' => [
                'class' => NestedSetsBehavior::class,
            ],
            'slug' => [
                'class' => SlugBehavior::class,
            ],
        ];
    }

    public static function tableName(): string
    {
        return 'multiple_behaviors_test';
    }
}
