<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\type\ContainerDynamicMethodReturnTypeExtensionTest;

/**
 * Type assertion datasets for {@see ContainerDynamicMethodReturnTypeExtensionTest}.
 */
final class ContainerDynamicMethodReturnTypeExtensionProvider
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from ContainerDynamicMethodReturnTypeExtensionTest::gatherAssertTypes(
            "{$directory}/data/type/ContainerDynamicMethodReturnType.php",
        );
    }
}
