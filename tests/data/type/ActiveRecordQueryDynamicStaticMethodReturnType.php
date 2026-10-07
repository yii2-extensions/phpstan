<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii\db\ActiveQuery;
use yii2\extensions\phpstan\tests\support\stub\{
    ActiveRecordQueryFactory,
    Comment,
    CreditInvoice,
    Invoice,
    MyActiveRecord,
    Post,
    PriorityTicket,
    Shipment,
    Ticket,
    Voucher,
};

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for the query model inference of {@see Invoice::find()} and other Active Record static methods
 * returning a non-generic custom query class.
 *
 * Covers the Gii-generated {@see Invoice} and {@see \yii2\extensions\phpstan\tests\support\stub\InvoiceQuery} pair, a
 * query class without overrides, a query bound to its model, subclasses, a `: self` scope on a subclass query, whose
 * rows come from the query class's own `one()` and `all()` overrides, every {@see ActiveQuery} method whose type
 * depends on the row type, and the calls that must stay unchanged.
 */
final class ActiveRecordQueryDynamicStaticMethodReturnType
{
    public function testKeepBoundQueryWhenFindOnBoundModel(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\TicketQuery',
            Ticket::find(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Ticket|null',
            Ticket::find()->open()->one(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\CommentQuery',
            Comment::find(),
        );
    }

    public function testKeepGenericQueryWhenModelHasNoCustomOrGenericQueryClass(): void
    {
        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\MyActiveRecord>',
            MyActiveRecord::find(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\PostQuery<yii2\extensions\phpstan\tests\support\stub\Post>',
            Post::find(),
        );
    }

    public function testKeepInferredQueryWhenCacheAndExecutionMethodIsCalled(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->cache(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->noCache(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->emulateExecution(),
        );
    }

    public function testKeepInferredQueryWhenJoinMethodIsCalled(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->join('LEFT JOIN', 'item'),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->innerJoin('item'),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->leftJoin('item'),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->rightJoin('item'),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->joinWith('comments'),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->innerJoinWith('comments'),
        );
    }

    public function testKeepInferredQueryWhenLimitOffsetIndexMethodIsCalled(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->limit(10),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->offset(10),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->indexBy('id'),
        );
    }

    public function testKeepInferredQueryWhenOrderGroupHavingMethodIsCalled(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->orderBy('id'),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->addOrderBy('id'),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->groupBy('id'),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->addGroupBy('id'),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->having(['id' => 1]),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->andHaving(['id' => 1]),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->orHaving(['id' => 1]),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->filterHaving(['id' => 1]),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->andFilterHaving(['id' => 1]),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->orFilterHaving(['id' => 1]),
        );
    }

    public function testKeepInferredQueryWhenRelationalMethodIsCalled(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->with('comments'),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->via('comments'),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->viaTable('invoice_comment', ['invoice_id' => 'id']),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->inverseOf('invoice'),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->onCondition(['id' => 1]),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->andOnCondition(['id' => 1]),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->orOnCondition(['id' => 1]),
        );
    }

    public function testKeepInferredQueryWhenSelectAndFromMethodIsCalled(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->select('id'),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->addSelect('id'),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->distinct(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->from('invoice'),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->alias('i'),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->params([]),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->addParams([]),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->withQuery(Invoice::find(), 'w'),
        );
    }

    public function testKeepInferredQueryWhenUnionMethodIsCalled(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->union(Invoice::find()),
        );
    }

    public function testKeepInferredQueryWhenWhereFilterMethodIsCalled(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->where(['id' => 1]),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->andWhere(['id' => 1]),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->orWhere(['id' => 1]),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->filterWhere(['id' => 1]),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->andFilterWhere(['id' => 1]),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->orFilterWhere(['id' => 1]),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->andFilterCompare('id', '>1'),
        );
    }

    public function testKeepNativeTypeWhenStaticCallIsNotInferable(): void
    {
        assertType(
            'string',
            Voucher::code(),
        );
        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Voucher>',
            Voucher::find(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery|yii2\extensions\phpstan\tests\support\stub\ShipmentQuery',
            Voucher::findEither(true),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery',
            ActiveRecordQueryFactory::invoices(),
        );
        assertType(
            'Closure(): yii2\extensions\phpstan\tests\support\stub\InvoiceQuery',
            Invoice::find(...),
        );
    }

    public function testKeepRowIndependentTypesWhenQueryIsInferred(): void
    {
        assertType(
            'array',
            Invoice::find()->populate([]),
        );
        assertType(
            'yii\db\Command',
            Invoice::find()->createCommand(),
        );
        assertType(
            'int|string|null',
            Invoice::find()->count(),
        );
        assertType(
            'bool',
            Invoice::find()->exists(),
        );
        assertType(
            'int|string|false|null',
            Invoice::find()->scalar(),
        );
        assertType(
            'array',
            Invoice::find()->column(),
        );
        assertType(
            'yii\base\Behavior<yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>>|null',
            Invoice::find()->getBehavior('timestamp'),
        );
    }

    public function testKeepStaticQueryMethodsUnchangedWhenModelHasCustomQueryClass(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            Invoice::findOne(1),
        );
        assertType(
            'array<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::findAll([1, 2]),
        );
        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::findBySql('SELECT * FROM invoice'),
        );
        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Comment>',
            (new Invoice())->hasMany(Comment::class, ['invoice_id' => 'id']),
        );
        assertType(
            'yii\db\ActiveQuery',
            (new Invoice())->getComments(),
        );
    }

    public function testReturnInvoiceRowShapeWhenAsArrayOnGiiGeneratedModel(): void
    {
        assertType(
            'yii\db\ActiveQuery<array{id: int, number: string, comments?: array<array<string, mixed>>}>',
            Invoice::find()->asArray(),
        );
        assertType(
            'array{id: int, number: string, comments?: array<array<string, mixed>>}|null',
            Invoice::find()->where(['id' => 1])->asArray()->one(),
        );
        assertType(
            'array<array{id: int, number: string, comments?: array<array<string, mixed>>}>',
            Invoice::find()->asArray()->all(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            Invoice::find()->asArray(false)->one(),
        );
    }

    public function testReturnInvoiceWhenBatchOrEachOnGiiGeneratedModel(): void
    {
        foreach (Invoice::find()->where(['id' => 1])->batch(10) as $invoices) {
            assertType(
                'array<yii2\extensions\phpstan\tests\support\stub\Invoice>',
                $invoices,
            );
        }

        foreach (Invoice::find()->each() as $invoice) {
            assertType(
                'yii2\extensions\phpstan\tests\support\stub\Invoice',
                $invoice,
            );
        }
    }

    /**
     * @param class-string<Invoice> $modelClass
     */
    public function testReturnInvoiceWhenFindOnClassStringOrObject(string $modelClass, Invoice $invoice): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            $modelClass::find()->one(),
        );
        assertType(
            'array<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            $modelClass::find()->all(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            $invoice::find()->one(),
        );
    }

    public function testReturnInvoiceWhenFindOnGiiGeneratedModel(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            Invoice::find()->one(),
        );
        assertType(
            'array<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->all(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            \yii2\extensions\phpstan\tests\support\stub\Invoice::find()->where(['id' => 1])->one(),
        );
        assertType(
            'array<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->where(['id' => 1])->andWhere(['number' => 'A'])->orderBy('id')->limit(5)->with('comments')
                ->indexBy('id')->all(),
        );
    }

    public function testReturnInvoiceWhenSelfScopeFollowsFindOnSubclass(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery',
            CreditInvoice::find()->paid(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            CreditInvoice::find()->paid()->one(),
        );
        assertType(
            'array<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            CreditInvoice::find()->paid()->all(),
        );
    }

    public function testReturnShipmentWhenFindOnModelWithBareQueryClass(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\ShipmentQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Shipment>',
            Shipment::find(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Shipment|null',
            Shipment::find()->one(),
        );
        assertType(
            'array<yii2\extensions\phpstan\tests\support\stub\Shipment>',
            Shipment::find()->with('items')->indexBy('id')->all(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Shipment|null',
            Shipment::find()->pending()->where(['id' => 1])->shipped()->one(),
        );
        assertType(
            'array{id: int, carrier: string}|null',
            Shipment::find()->pending()->asArray()->one(),
        );

        foreach (Shipment::find()->batch() as $shipments) {
            assertType(
                'array<yii2\extensions\phpstan\tests\support\stub\Shipment>',
                $shipments,
            );
        }

        foreach (Shipment::find()->each() as $shipment) {
            assertType(
                'yii2\extensions\phpstan\tests\support\stub\Shipment',
                $shipment,
            );
        }
    }

    public function testReturnSubclassWhenFindOnSubclassOfModel(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\CreditInvoice>',
            CreditInvoice::find(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\CreditInvoice|null',
            CreditInvoice::find()->one(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\TicketQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\PriorityTicket>',
            PriorityTicket::find(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\PriorityTicket|null',
            PriorityTicket::find()->open()->one(),
        );
        assertType(
            'array<yii2\extensions\phpstan\tests\support\stub\PriorityTicket>',
            PriorityTicket::find()->where(['id' => 1])->all(),
        );

        foreach (PriorityTicket::find()->each() as $ticket) {
            assertType(
                'yii2\extensions\phpstan\tests\support\stub\PriorityTicket',
                $ticket,
            );
        }
    }

    public function testReturnWideTypeWhenScopeReturnsSelfOrHasNoReturnType(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\ShipmentQuery',
            Shipment::find()->delivered(),
        );
        assertType(
            'mixed',
            Shipment::find()->returned(),
        );
    }
}
