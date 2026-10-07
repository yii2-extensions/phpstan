<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveRecord;

/**
 * Stub generic base ActiveRecord model declaring a `@property` tag typed by its template parameter.
 *
 * @template T
 *
 * @property int $id
 * @property T $value
 */
class TypedValueRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'typed_values';
    }
}
