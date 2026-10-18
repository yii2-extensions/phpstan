<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\method\BehaviorMethodsClassReflectionExtensionTest;

/**
 * Type assertion datasets for {@see BehaviorMethodsClassReflectionExtensionTest}.
 */
final class BehaviorMethodsClassReflectionExtensionProvider
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from BehaviorMethodsClassReflectionExtensionTest::gatherAssertTypes(
            "{$directory}/data/method/BehaviorMethodsClassReflectionType.php",
        );
    }
}
