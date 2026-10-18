<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\type\ActiveRecordGetAttributeDynamicMethodReturnTypeExtensionTest;

/**
 * Type assertion datasets for {@see ActiveRecordGetAttributeDynamicMethodReturnTypeExtensionTest}.
 */
final class ActiveRecordGetAttributeDynamicMethodReturnTypeExtensionProvider
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from ActiveRecordGetAttributeDynamicMethodReturnTypeExtensionTest::gatherAssertTypes(
            "{$directory}/data/type/ActiveRecordGetAttributeDynamicMethodReturnType.php",
        );
    }
}
