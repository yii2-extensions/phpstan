<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use function strlen;

/**
 * Stub consumer of the Gii-generated {@see InvoiceQuery} reached without a call site naming the model, whose code must
 * report no errors at level `max`.
 */
final class ActiveQueryDerivedModelConsumer
{
    public function __construct(private readonly InvoiceQuery $invoiceQuery) {}

    public function countNumbers(InvoiceQuery $query): int
    {
        $length = 0;

        foreach ($query->all() as $invoice) {
            $length += strlen($invoice->number);
        }

        foreach ($this->invoiceQuery->paid()->each() as $invoice) {
            $length += strlen($invoice->number);
        }

        return $length;
    }

    public function findNumber(CreditInvoiceQuery $query): string|null
    {
        return $query->refunded()->one()?->number;
    }

    public function load(InvoiceQuery $query): string
    {
        $invoice = $query->one();

        if ($invoice === null) {
            return '';
        }

        return $invoice->number;
    }

    public function loadRow(): string
    {
        $row = $this->invoiceQuery->paid()->asArray()->one();

        return $row === null ? '' : $row['number'];
    }
}
