<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\stub;

use yii\db\ActiveRecord;

/**
 * Active Record model for testing multiple behavior attachments and property aggregation.
 *
 * This class demonstrates a scenario where a model attaches multiple behaviors simultaneously, each contributing
 * different properties and methods to the model instance. The model combines {@see NestedSetsBehavior} for tree
 * structure operations and {@see SlugBehavior} for URL-friendly slug generation.
 *
 * This model is specifically designed for testing PHPStan extensions and their ability to handle multiple behavior
 * attachments, ensuring proper property and method resolution when behaviors are combined in Yii2 applications.
 *
 * The behaviors work independently but are managed together by the model, allowing testing of property aggregation,
 * method resolution, and potential conflicts between multiple behavior implementations.
 *
 * Key features:
 * - Multiple behavior attachment with property aggregation.
 * - Nested sets functionality for hierarchical data structures.
 * - Property resolution testing for multiple behaviors.
 * - Slug generation capability for URL-friendly identifiers.
 * - Static analysis validation for behavior combination.
 * - Table mapping with multiple_behaviors_test table.
 *
 * @property int $depth
 * @property int $lft
 * @property int $rgt
 * @property string $slug
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
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
