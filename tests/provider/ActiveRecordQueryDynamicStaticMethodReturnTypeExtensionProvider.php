<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii\db\Query;
use yii2\extensions\phpstan\tests\type\ActiveRecordQueryDynamicStaticMethodReturnTypeExtensionTest;

use function get_class_methods;
use function in_array;

/**
 * Type assertion datasets for {@see ActiveRecordQueryDynamicStaticMethodReturnTypeExtensionTest}.
 */
final class ActiveRecordQueryDynamicStaticMethodReturnTypeExtensionProvider
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from ActiveRecordQueryDynamicStaticMethodReturnTypeExtensionTest::gatherAssertTypes(
            "{$directory}/data/type/ActiveRecordQueryDynamicStaticMethodReturnType.php",
        );
        yield from ActiveRecordQueryDynamicStaticMethodReturnTypeExtensionTest::gatherAssertTypes(
            "{$directory}/data/type/ActiveRecordQueryInvoiceRecord.php",
        );
        yield from ActiveRecordQueryDynamicStaticMethodReturnTypeExtensionTest::gatherAssertTypes(
            "{$directory}/data/type/ActiveRecordQueryShipmentRecord.php",
        );
        yield from ActiveRecordQueryDynamicStaticMethodReturnTypeExtensionTest::gatherAssertTypes(
            "{$directory}/data/type/ActiveRecordQueryTicketRecord.php",
        );
        yield from ActiveRecordQueryDynamicStaticMethodReturnTypeExtensionTest::gatherAssertTypes(
            "{$directory}/data/type/ActiveRecordQueryDynamicStaticMethodNameType.php",
        );

        if (in_array('unionOrderBy', get_class_methods(Query::class), true)) {
            yield from ActiveRecordQueryDynamicStaticMethodReturnTypeExtensionTest::gatherAssertTypes(
                "{$directory}/data/type/ActiveRecordQueryDynamicStaticMethodUnionBatchType.php",
            );
        }
    }
}
