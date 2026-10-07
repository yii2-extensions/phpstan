<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveQuery;

/**
 * Stub consumer of the Gii-generated {@see Invoice} model whose code must report no errors at level `max`.
 */
final class ActiveRecordQueryInvoiceConsumer
{
    /**
     * @var ActiveQuery<Invoice>
     */
    private ActiveQuery $activeQuery;
    private InvoiceQuery $invoiceQuery;

    public function __construct()
    {
        $this->invoiceQuery = Invoice::find();
        $this->activeQuery = Invoice::find()->where(['id' => 1]);
    }

    /**
     * @return Invoice[]
     */
    public function findAll(): array
    {
        return Invoice::find()->all();
    }

    public function findLatest(): Invoice|null
    {
        return Invoice::find()->orderBy(['id' => SORT_DESC])->one();
    }

    public function findNumber(): string|null
    {
        return Invoice::find()->where(['id' => 1])->one()?->number;
    }

    public function printNumbers(): void
    {
        foreach (Invoice::find()->all() as $invoice) {
            echo $invoice->number;
        }

        foreach ($this->activeQuery->each() as $invoice) {
            echo $invoice->number;
        }
    }

    /**
     * @return ActiveQuery<Invoice>
     */
    public function queryActive(): ActiveQuery
    {
        return $this->acceptActiveQuery(Invoice::find());
    }

    public function queryInvoices(): InvoiceQuery
    {
        return $this->acceptInvoiceQuery(Invoice::find()->where(['id' => 1]));
    }

    public function save(): bool
    {
        $invoice = Invoice::find()->one();

        if ($invoice !== null) {
            return $invoice->save();
        }

        return $this->invoiceQuery->exists();
    }

    /**
     * @param ActiveQuery<Invoice> $query
     *
     * @return ActiveQuery<Invoice>
     */
    private function acceptActiveQuery(ActiveQuery $query): ActiveQuery
    {
        return $query;
    }

    private function acceptInvoiceQuery(InvoiceQuery $query): InvoiceQuery
    {
        return $query;
    }
}
