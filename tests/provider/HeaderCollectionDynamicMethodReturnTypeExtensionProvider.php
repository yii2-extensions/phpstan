<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\type\HeaderCollectionDynamicMethodReturnTypeExtensionTest;

/**
 * Type assertion datasets for {@see HeaderCollectionDynamicMethodReturnTypeExtensionTest}.
 */
final class HeaderCollectionDynamicMethodReturnTypeExtensionProvider
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from HeaderCollectionDynamicMethodReturnTypeExtensionTest::gatherAssertTypes(
            "{$directory}/data/type/HeaderCollectionDynamicMethodReturnType.php",
        );
    }
}
