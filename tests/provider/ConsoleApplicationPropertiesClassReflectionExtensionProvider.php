<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\console\property\ApplicationPropertiesClassReflectionExtensionTest;

/**
 * Type assertion datasets for {@see ApplicationPropertiesClassReflectionExtensionTest}.
 */
final class ConsoleApplicationPropertiesClassReflectionExtensionProvider
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__) . '/console';

        yield from ApplicationPropertiesClassReflectionExtensionTest::gatherAssertTypes(
            "{$directory}/data/property/ApplicationPropertiesClassReflectionType.php",
        );
    }
}
