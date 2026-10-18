<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\property\BehaviorPropertiesClassReflectionExtensionTest;

/**
 * Type assertion datasets for {@see BehaviorPropertiesClassReflectionExtensionTest}.
 */
final class BehaviorPropertiesClassReflectionExtensionProvider
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from BehaviorPropertiesClassReflectionExtensionTest::gatherAssertTypes(
            "{$directory}/data/property/BehaviorPropertiesClassReflectionType.php",
        );
    }
}
