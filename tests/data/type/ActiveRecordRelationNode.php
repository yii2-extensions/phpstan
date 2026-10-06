<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii\db\ActiveRecord;

use function PHPStan\Testing\assertType;

/**
 * Self-referencing model for `static::class` relation type assertions.
 *
 * @property int $id
 * @property int|null $parent_id
 */
class ActiveRecordRelationNode extends ActiveRecord
{
    public function testReturnStaticQueryWhenHasManyWithStaticClass(): void
    {
        assertType(
            'yii\db\ActiveQuery<static(yii2\extensions\phpstan\tests\data\type\ActiveRecordRelationNode)>',
            $this->hasMany(static::class, ['parent_id' => 'id']),
        );
        assertType(
            'array<array{id: int, parent_id: int|null}>',
            $this->hasMany(static::class, ['parent_id' => 'id'])->asArray()->all(),
        );
    }
}
