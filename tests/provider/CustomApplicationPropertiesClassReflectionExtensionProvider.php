<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\custom\property\ApplicationPropertiesClassReflectionExtensionTest;

/**
 * Type assertion datasets for {@see ApplicationPropertiesClassReflectionExtensionTest}.
 */
final class CustomApplicationPropertiesClassReflectionExtensionProvider
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__) . '/custom';

        yield from ApplicationPropertiesClassReflectionExtensionTest::gatherAssertTypes(
            "{$directory}/data/property/ApplicationPropertiesClassReflectionType.php",
        );
    }
}
