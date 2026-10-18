<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\type\ServiceMapDefinitionTypeTest;

/**
 * Type assertion datasets for {@see ServiceMapDefinitionTypeTest}.
 */
final class ServiceMapDefinitionTypeProvider
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from ServiceMapDefinitionTypeTest::gatherAssertTypes(
            "{$directory}/data/type/ServiceMapDefinitionType.php",
        );
    }
}
