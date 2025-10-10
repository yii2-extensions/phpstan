<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveRecord;

/**
 * Active Record model for testing post-data and dynamic query return types.
 *
 * Provides a simple Active Record implementation with string properties for post-content management, demonstrating
 * custom query class integration and type-safe database operations for blog-like content scenarios.
 *
 * This class is used in type inference and static analysis tests to validate PHPStan's ability to correctly infer
 * return types for custom ActiveQuery implementations and chained method calls on post-related queries.
 *
 * The model demonstrates the use of custom query classes through the {@see PostQuery} implementation, enabling
 * specialized query methods and maintaining type safety across database operations.
 *
 * Key features:
 * - Custom query class integration with {@see PostQuery}.
 * - Property validation for post-content (title and content).
 * - Static analysis validation for custom query return types.
 * - String property definitions for post-data.
 * - Table mapping with post table.
 * - Type-safe query method implementation.
 *
 * @property string $title
 * @property string $content
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class Post extends ActiveRecord
{
    /**
     * @return PostQuery<static>
     */
    public static function find(): PostQuery
    {
        return new PostQuery(self::class);
    }

    public static function tableName(): string
    {
        return 'posts';
    }
}
