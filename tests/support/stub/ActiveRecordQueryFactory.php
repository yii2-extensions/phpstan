<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

/**
 * Stub non-Active Record class with a static method returning a custom query class, for query model inference tests.
 */
final class ActiveRecordQueryFactory
{
    public static function invoices(): InvoiceQuery
    {
        return new InvoiceQuery(Invoice::class);
    }
}
