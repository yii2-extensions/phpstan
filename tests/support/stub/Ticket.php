<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveRecord;

/**
 * Stub ActiveRecord model with {@see TicketQuery} bound to it for query model inference tests.
 *
 * @property int $id
 * @property string $subject
 */
class Ticket extends ActiveRecord
{
    public static function find(): TicketQuery
    {
        return new TicketQuery(static::class);
    }

    public static function tableName(): string
    {
        return 'tickets';
    }
}
