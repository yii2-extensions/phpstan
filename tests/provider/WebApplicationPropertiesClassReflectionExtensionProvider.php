<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\web\property\ApplicationPropertiesClassReflectionExtensionTest;

/**
 * Type assertion datasets for {@see ApplicationPropertiesClassReflectionExtensionTest}.
 */
final class WebApplicationPropertiesClassReflectionExtensionProvider
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__) . '/web';

        yield from ApplicationPropertiesClassReflectionExtensionTest::gatherAssertTypes(
            "{$directory}/data/property/ApplicationPropertiesClassReflectionType.php",
        );
    }
}
