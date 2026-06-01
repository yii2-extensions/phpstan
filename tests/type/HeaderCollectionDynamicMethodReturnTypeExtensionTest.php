<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use yii\web\HeaderCollection;

/**
 * Test suite for type inference of dynamic method return types in {@see HeaderCollection} for Yii component scenarios.
 *
 * Validates that PHPStan correctly infers types for dynamic HeaderCollection method calls and result sets in custom
 * {@see HeaderCollection} usage, using fixture-based assertions for header retrieval, value resolution, and
 * return type inference.
 *
 * The test class loads type assertions from a fixture file and delegates checks to the parent
 * {@see TypeInferenceTestCase}, ensuring that extension logic for {@see HeaderCollection} dynamic method return types
 * is robust and consistent with expected behavior.
 */
final class HeaderCollectionDynamicMethodReturnTypeExtensionTest extends TypeInferenceTestCase
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from self::gatherAssertTypes(
            "{$directory}/data/type/HeaderCollectionDynamicMethodReturnType.php",
        );
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-test.neon'];
    }

    #[DataProvider('dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
