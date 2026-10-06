<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\web\type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use yii2\extensions\phpstan\type\ApplicationTypeNodeResolverExtension;

/**
 * Unit tests for the stub-declared `Yii::$app` and `Module::$params` types resolved by
 * {@see ApplicationTypeNodeResolverExtension} with a web application and params.
 */
final class ApplicationParamsTypeTest extends TypeInferenceTestCase
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from self::gatherAssertTypes("{$directory}/data/type/ApplicationParamsType.php");
        yield from self::gatherAssertTypes("{$directory}/data/type/ApplicationParamsModule.php");
        yield from self::gatherAssertTypes("{$directory}/data/type/ApplicationParamsOwnParamsModule.php");
        yield from self::gatherAssertTypes("{$directory}/data/type/ApplicationParamsController.php");
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__, 2) . '/support/extension-params-test.neon'];
    }

    #[DataProvider('dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
