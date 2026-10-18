<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\web\property\ApplicationParamsTypeTest;

/**
 * Type assertion datasets for {@see ApplicationParamsTypeTest}.
 */
final class WebApplicationParamsPropertyProvider
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__) . '/web';

        yield from ApplicationParamsTypeTest::gatherAssertTypes(
            "{$directory}/data/property/ApplicationParamsType.php",
        );
    }
}
