<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveRecord;

/**
 * Stub generated base ActiveRecord model whose `@property` tags are inherited by {@see Article} and
 * {@see ArticleDraft}.
 *
 * @property int $id
 * @property string $title
 * @property int|string $revision
 * @phpstan-property-write string $password
 */
class ArticleRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'articles';
    }
}
