<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\property\UserPropertiesClassReflectionExtensionTest;

/**
 * Type assertion datasets for {@see UserPropertiesClassReflectionExtensionTest}.
 */
final class UserPropertiesClassReflectionExtensionProvider
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from UserPropertiesClassReflectionExtensionTest::gatherAssertTypes(
            "{$directory}/data/property/UserPropertiesClassReflectionType.php",
        );
    }
}
