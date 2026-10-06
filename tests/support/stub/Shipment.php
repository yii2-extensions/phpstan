<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveRecord;

/**
 * Stub ActiveRecord model with the non-generic {@see ShipmentQuery} for query model inference tests.
 *
 * @property int $id
 * @property string $carrier
 */
class Shipment extends ActiveRecord
{
    public static function find(): ShipmentQuery
    {
        return new ShipmentQuery(static::class);
    }

    public static function tableName(): string
    {
        return 'shipments';
    }
}
