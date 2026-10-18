<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\web\type\ParamsAwkwardKeysTypeTest;

/**
 * Type assertion datasets for {@see ParamsAwkwardKeysTypeTest}.
 */
final class WebParamsAwkwardKeysTypeProvider
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__) . '/web';

        yield from ParamsAwkwardKeysTypeTest::gatherAssertTypes("{$directory}/data/type/ParamsAwkwardKeysType.php");
    }
}
