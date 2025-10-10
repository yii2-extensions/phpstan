<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveRecord;

/**
 * Active Record model for testing property conflicts between model and behavior definitions.
 *
 * This class demonstrates a scenario where a model defines a property that conflicts with a property defined in an
 * attached behavior. The model defines a `lft` property as string while the {@see NestedSetsBehavior} defines the same
 * property as int, creating a type conflict for static analysis tools.
 *
 * This model is specifically designed for testing PHPStan extensions and their ability to handle property conflicts
 * between behaviors and model declarations, ensuring proper type resolution and conflict detection in Yii2
 * applications.
 *
 * The conflict occurs when the behavior's property type differs from the model's declared property type, which requires
 * careful handling by static analysis tools to provide accurate type information.
 *
 * Key features:
 * - Behavior attachment with conflicting property types.
 * - Property conflict demonstration for testing purposes.
 * - Static analysis validation for type resolution.
 * - Table mapping with conflict_test table.
 * - Type conflict between string and int for lft property.
 *
 * @property string $lft
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
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
