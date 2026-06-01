<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test suite for type inference of dynamic method return types in {@see ActiveRecord} relations for Yii Active Record
 * scenarios.
 *
 * Validates that PHPStan correctly infers types for dynamic relation methods and result sets in custom
 * {@see ActiveRecord} implementations, using fixture-based assertions for chained query calls, array/object result
 * scenarios, and property access.
 *
 * The test class loads type assertions from a fixture file and delegates checks to the parent
 * {@see TypeInferenceTestCase}, ensuring that extension logic for {@see ActiveRecord} dynamic method return types is
 * robust and consistent with expected behavior.
 */
final class ActiveRecordDynamicMethodReturnTypeExtensionTest extends TypeInferenceTestCase
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from self::gatherAssertTypes(
            "{$directory}/data/type/ActiveRecordDynamicMethodReturnType.php",
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
