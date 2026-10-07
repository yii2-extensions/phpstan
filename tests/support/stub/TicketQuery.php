<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveQuery;

/**
 * Stub non-generic ActiveQuery bound to {@see Ticket} with a chainable `open()` method for query model inference tests.
 *
 * @extends ActiveQuery<Ticket>
 */
final class TicketQuery extends ActiveQuery
{
    public function open(): static
    {
        return $this->andWhere(['open' => 1]);
    }
}
