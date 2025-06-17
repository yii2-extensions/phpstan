<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\stub;

use yii\db\ActiveRecord;

/**
 * Active Record model for testing nested sets behavior attachment and property resolution.
 *
 * This class demonstrates a scenario where a model attaches the {@see NestedSetsBehavior} to enable hierarchical data
 * structure operations. The model provides tree structure functionality through the behavior's properties and methods,
 * allowing for nested sets model implementation on Active Record instances.
 *
 * This model is specifically designed for testing PHPStan extensions and their ability to handle behavior property
 * resolution, ensuring proper type inference and property access when behaviors are attached to Yii Active Record
 * models.
 *
 * The nested sets behavior provides three integer properties (depth, lft, rgt) that are used to represent hierarchical
 * relationships in database tables, enabling efficient tree structure queries and operations.
 *
 * Key features:
 * - Behavior attachment for hierarchical data structures.
 * - Nested sets model functionality for tree operations.
 * - Property resolution testing for behavior integration.
 * - Static analysis validation for behavior property access.
 * - Table mapping with nested_sets_test table.
 *
 * @property int $depth
 * @property int $lft
 * @property int $rgt
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
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
