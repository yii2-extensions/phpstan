<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\custom\property;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for type inference of property reflection in a custom Yii Application for PHPStan analysis.
 *
 * Validates that PHPStan correctly infers the custom application type for `Yii::$app` when a user-defined application
 * class is configured.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.4.1
 */
final class ApplicationPropertiesClassReflectionExtensionTest extends TypeInferenceTestCase
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from self::gatherAssertTypes(
            "{$directory}/data/property/ApplicationPropertiesClassReflectionType.php",
        );
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/extension-custom-test.neon'];
    }

    #[DataProvider('dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
