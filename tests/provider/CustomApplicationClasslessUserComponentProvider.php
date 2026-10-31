<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\custom\user\ClasslessUserComponentTest;

/**
 * Type assertion datasets for {@see ClasslessUserComponentTest}.
 */
final class CustomApplicationClasslessUserComponentProvider
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__) . '/custom';

        yield from ClasslessUserComponentTest::gatherAssertTypes(
            "{$directory}/data/user/ClasslessUserComponentType.php",
        );
    }
}
