<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\type\ActiveRecordRelationReturnTypeTest;

/**
 * Type assertion datasets for {@see ActiveRecordRelationReturnTypeTest}.
 */
final class ActiveRecordRelationReturnTypeProvider
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from ActiveRecordRelationReturnTypeTest::gatherAssertTypes(
            "{$directory}/data/type/ActiveRecordRelationReturnType.php",
        );
        yield from ActiveRecordRelationReturnTypeTest::gatherAssertTypes(
            "{$directory}/data/type/ActiveRecordRelationNode.php",
        );
    }
}
