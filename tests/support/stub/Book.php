<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveRecord;

/**
 * Stub ActiveRecord model mixing column, relation, read-only, and write-only `@property` tags for `asArray()` row shape
 * tests.
 *
 * @property int $id
 * @property string|null $title
 * @phpstan-property-read Comment[] $comments
 * @phpstan-property-read User $author
 * @property Category|null $category
 * @phpstan-property-read string $label
 * @phpstan-property-write string $secret
 */
final class Book extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'books';
    }
}
