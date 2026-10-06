<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii2\extensions\phpstan\tests\support\stub\Ticket;

use function PHPStan\Testing\assertType;

/**
 * Subclass of {@see Ticket} calling its bound query class through `self`, `static`, and `parent`.
 */
class ActiveRecordQueryTicketRecord extends Ticket
{
    public function testReturnStaticModelWhenFindCalledInsideModel(): void
    {
        assertType(
            'static(yii2\extensions\phpstan\tests\data\type\ActiveRecordQueryTicketRecord)|null',
            self::find()->one(),
        );
        assertType(
            'array<static(yii2\extensions\phpstan\tests\data\type\ActiveRecordQueryTicketRecord)>',
            static::find()->open()->all(),
        );
        assertType(
            'static(yii2\extensions\phpstan\tests\data\type\ActiveRecordQueryTicketRecord)|null',
            parent::find()->one(),
        );
    }
}
