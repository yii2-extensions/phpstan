<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\property;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test suite for type inference of property reflection in Yii Application classes for PHPStan analysis.
 *
 * Validates that PHPStan correctly infers types for properties provided by different Yii application variants (console,
 * web, and custom) using fixture-based assertions for direct property access and property resolution.
 *
 * The test class loads type assertions from multiple fixture files and delegates checks to the parent
 * {@see TypeInferenceTestCase}, ensuring that extension logic for {@see ApplicationPropertiesClassReflectionExtension}
 * is robust and consistent with expected behavior.
 *
 * Key features.
 * - Ensures compatibility with PHPStan extension configuration for Yii application classes.
 * - Loads and executes type assertions from dedicated fixture files for each application type.
 * - Uses PHPUnit DataProvider for parameterized test execution.
 * - Validates type inference for native and custom application properties.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
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
            "{$directory}/fixture/data/property/ApplicationConsolePropertiesClassReflectionType.php",
        );
        yield from self::gatherAssertTypes(
            "{$directory}/fixture/data/property/ApplicationCustomPropertiesClassReflectionType.php",
        );
        yield from self::gatherAssertTypes(
            "{$directory}/fixture/data/property/ApplicationWebPropertiesClassReflectionType.php",
        );
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/extension-tests.neon'];
    }

    #[DataProvider('dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
