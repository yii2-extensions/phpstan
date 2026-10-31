<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\custom\property\CustomApplicationMembersTest;

/**
 * Type assertion datasets for {@see CustomApplicationMembersTest}.
 */
final class CustomApplicationMembersProvider
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__) . '/custom';

        yield from CustomApplicationMembersTest::gatherAssertTypes(
            "{$directory}/data/property/CustomApplicationMembersType.php",
        );
    }
}
