<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\custom\user\ConfiguredUserComponentTest;

/**
 * Type assertion datasets for {@see ConfiguredUserComponentTest}.
 */
final class CustomApplicationConfiguredUserComponentProvider
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__) . '/custom';

        yield from ConfiguredUserComponentTest::gatherAssertTypes(
            "{$directory}/data/user/ConfiguredUserComponentType.php",
        );
    }
}
