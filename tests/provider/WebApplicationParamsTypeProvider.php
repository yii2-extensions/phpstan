<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\web\type\ApplicationParamsTypeTest;

/**
 * Type assertion datasets for {@see ApplicationParamsTypeTest}.
 */
final class WebApplicationParamsTypeProvider
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__) . '/web';

        yield from ApplicationParamsTypeTest::gatherAssertTypes(
            "{$directory}/data/type/ApplicationParamsType.php",
        );
        yield from ApplicationParamsTypeTest::gatherAssertTypes(
            "{$directory}/data/type/ApplicationParamsModule.php",
        );
        yield from ApplicationParamsTypeTest::gatherAssertTypes(
            "{$directory}/data/type/ApplicationParamsOwnParamsModule.php",
        );
        yield from ApplicationParamsTypeTest::gatherAssertTypes(
            "{$directory}/data/type/ApplicationParamsController.php",
        );
    }
}
