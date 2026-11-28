<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii\BaseYii;
use yii2\extensions\phpstan\tests\type\HeaderCollectionDynamicMethodReturnTypeExtensionTest;

use function dirname;
use function version_compare;

/**
 * Type assertion datasets for {@see HeaderCollectionDynamicMethodReturnTypeExtensionTest}, from the fixture that matches
 * the installed Yii version.
 */
final class HeaderCollectionDynamicMethodReturnTypeExtensionProvider
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        $fixture = version_compare(BaseYii::getVersion(), '22', '>=')
            ? 'HeaderCollectionDynamicMethodReturnTypeYii22.php'
            : 'HeaderCollectionDynamicMethodReturnType.php';

        yield from HeaderCollectionDynamicMethodReturnTypeExtensionTest::gatherAssertTypes(
            "{$directory}/data/type/{$fixture}",
        );
    }
}
