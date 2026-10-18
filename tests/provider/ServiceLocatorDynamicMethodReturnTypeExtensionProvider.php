<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\type\ServiceLocatorDynamicMethodReturnTypeExtensionTest;

/**
 * Type assertion datasets for {@see ServiceLocatorDynamicMethodReturnTypeExtensionTest}.
 */
final class ServiceLocatorDynamicMethodReturnTypeExtensionProvider
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from ServiceLocatorDynamicMethodReturnTypeExtensionTest::gatherAssertTypes(
            "{$directory}/data/type/ServiceLocatorDynamicMethodReturnType.php",
        );
    }
}
