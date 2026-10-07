<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii2\extensions\phpstan\tests\support\stub\Shipment;

use function PHPStan\Testing\assertType;

/**
 * Subclass of {@see Shipment} calling its bare query class through `self`, `static`, and `parent`.
 */
class ActiveRecordQueryShipmentRecord extends Shipment
{
    public function testReturnStaticModelWhenFindCalledInsideModel(): void
    {
        assertType(
            'static(yii2\extensions\phpstan\tests\data\type\ActiveRecordQueryShipmentRecord)|null',
            self::find()->pending()->one(),
        );
        assertType(
            'array<static(yii2\extensions\phpstan\tests\data\type\ActiveRecordQueryShipmentRecord)>',
            static::find()->shipped()->all(),
        );
        assertType(
            'static(yii2\extensions\phpstan\tests\data\type\ActiveRecordQueryShipmentRecord)|null',
            parent::find()->one(),
        );

        foreach (static::find()->batch() as $shipments) {
            assertType(
                'array<static(yii2\extensions\phpstan\tests\data\type\ActiveRecordQueryShipmentRecord)>',
                $shipments,
            );
        }
    }
}
