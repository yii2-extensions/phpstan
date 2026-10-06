<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use yii\db\{ActiveRecord, Query};
use yii2\extensions\phpstan\type\ActiveRecordQueryDynamicStaticMethodReturnTypeExtension;

use function in_array;

/**
 * Test suite for {@see ActiveRecordQueryDynamicStaticMethodReturnTypeExtension} binding the rows of a non-generic
 * custom query class returned by an {@see ActiveRecord} static method to the called model.
 *
 * Guards the inferred query type through every row-dependent {@see \yii\db\ActiveQuery} method, the late static
 * binding of `self::find()`, `static::find()`, and `parent::find()`, and the calls that must stay unchanged. The union
 * ordering methods and the generic {@see \yii\db\BatchQueryResult} are asserted only on Yii versions declaring them.
 */
final class ActiveRecordQueryDynamicStaticMethodReturnTypeExtensionTest extends TypeInferenceTestCase
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from self::gatherAssertTypes(
            "{$directory}/data/type/ActiveRecordQueryDynamicStaticMethodReturnType.php",
        );
        yield from self::gatherAssertTypes(
            "{$directory}/data/type/ActiveRecordQueryInvoiceRecord.php",
        );
        yield from self::gatherAssertTypes(
            "{$directory}/data/type/ActiveRecordQueryShipmentRecord.php",
        );
        yield from self::gatherAssertTypes(
            "{$directory}/data/type/ActiveRecordQueryTicketRecord.php",
        );
        yield from self::gatherAssertTypes(
            "{$directory}/data/type/ActiveRecordQueryDynamicStaticMethodNameType.php",
        );

        if (in_array('unionOrderBy', get_class_methods(Query::class), true)) {
            yield from self::gatherAssertTypes(
                "{$directory}/data/type/ActiveRecordQueryDynamicStaticMethodUnionBatchType.php",
            );
        }
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-test.neon'];
    }

    #[DataProvider('dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
