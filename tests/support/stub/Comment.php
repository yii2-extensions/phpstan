<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveRecord;

/**
 * Stub ActiveRecord model with a non-generic custom {@see CommentQuery} for query return type inference tests.
 *
 * @property int $id
 * @property string $body
 */
final class Comment extends ActiveRecord
{
    public static function find(): CommentQuery
    {
        return new CommentQuery(self::class);
    }

    public static function tableName(): string
    {
        return 'comments';
    }
}
