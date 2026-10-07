<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii\db\ActiveRecord;

use function PHPStan\Testing\assertType;

/**
 * Model calling its own static query methods for `static::find()` and `self::find()` type assertions.
 *
 * @property int $id
 * @property string $sku
 */
class ActiveRecordStaticMethodModel extends ActiveRecord
{
    public function testReturnStaticQueryWhenFindCalledInsideModel(): void
    {
        assertType(
            'yii\db\ActiveQuery<static(yii2\extensions\phpstan\tests\data\type\ActiveRecordStaticMethodModel)>',
            static::find(),
        );
        assertType(
            'yii\db\ActiveQuery<static(yii2\extensions\phpstan\tests\data\type\ActiveRecordStaticMethodModel)>',
            self::find(),
        );
        assertType(
            'array{id: int, sku: string}|null',
            static::find()->asArray()->one(),
        );
    }
}
