<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveRecord;

/**
 * Stub base ActiveRecord model whose column and relation `@property` tags are inherited by {@see Volume}.
 *
 * @property int $id
 * @phpstan-property-read Comment[] $comments
 */
class LibraryRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'volumes';
    }
}
