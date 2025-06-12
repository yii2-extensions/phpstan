<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\stub;

use yii\db\ActiveRecord;

/**
 * @property string $title
 * @property string $content
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
