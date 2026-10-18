<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\type\ActiveQueryDynamicReturnTypeExtensionTest;

/**
 * Type assertion datasets for {@see ActiveQueryDynamicReturnTypeExtensionTest}.
 */
final class ActiveQueryDynamicReturnTypeExtensionProvider
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from ActiveQueryDynamicReturnTypeExtensionTest::gatherAssertTypes(
            "{$directory}/data/type/ActiveQueryDynamicMethodReturnType.php",
        );
        yield from ActiveQueryDynamicReturnTypeExtensionTest::gatherAssertTypes(
            "{$directory}/data/type/ActiveQueryDerivedModelReturnType.php",
        );
        yield from ActiveQueryDynamicReturnTypeExtensionTest::gatherAssertTypes(
            "{$directory}/data/type/ActiveQueryDerivedModelEdgeType.php",
        );
    }
}
