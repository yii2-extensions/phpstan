<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\{ActiveQueryInterface, ActiveRecord};

/**
 * Stub ActiveRecord model whose static methods return no single query class, for query model inference tests.
 *
 * @property int $id
 */
final class Voucher extends ActiveRecord
{
    public static function code(): string
    {
        return 'voucher';
    }

    public static function find(): ActiveQueryInterface
    {
        return new ShipmentQuery(self::class);
    }

    public static function findEither(bool $shipment): InvoiceQuery|ShipmentQuery
    {
        return $shipment ? new ShipmentQuery(self::class) : new InvoiceQuery(self::class);
    }
}
