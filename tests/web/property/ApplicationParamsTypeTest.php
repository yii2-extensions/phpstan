<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\web\property;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for {@see ApplicationPropertiesClassReflectionExtension} params type inference.
 *
 * Validates that PHPStan correctly infers array shape types for `Yii::$app->params` when configured via the extension
 * configuration file.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.4.1
 */
final class ApplicationParamsTypeTest extends TypeInferenceTestCase
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from self::gatherAssertTypes(
            "{$directory}/data/property/ApplicationParamsType.php",
        );
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
