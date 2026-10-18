<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\type\ActiveRecordStaticMethodReturnTypeTest;

/**
 * Type assertion datasets for {@see ActiveRecordStaticMethodReturnTypeTest}.
 */
final class ActiveRecordStaticMethodReturnTypeProvider
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from ActiveRecordStaticMethodReturnTypeTest::gatherAssertTypes(
            "{$directory}/data/type/ActiveRecordStaticMethodReturnType.php",
        );
        yield from ActiveRecordStaticMethodReturnTypeTest::gatherAssertTypes(
            "{$directory}/data/type/ActiveRecordStaticMethodModel.php",
        );
    }
}
