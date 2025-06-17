<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test suite for type inference of dynamic return types in {@see ActiveRecord::getAttribute()} method calls.
 *
 * Validates that PHPStan correctly infers return types for {@see ActiveRecord::getAttribute()} method calls through the
 * custom PHPStan extension, using fixture-based assertions for model property access, behavior property resolution, and
 * type precedence scenarios.
 *
 * The test class loads type assertions from a fixture file and delegates checks to the parent
 * {@see TypeInferenceTestCase}, ensuring that extension logic for {@see ActiveRecord::getAttribute()} dynamic return
 * types provides accurate type inference for model properties and behavior-defined attributes.
 *
 * This validates the ability of the PHPStan extension to resolve property types from PHPDoc annotations in both model
 * classes and their attached behaviors, with proper precedence handling when property names conflict.
 *
 * Key features.
 * - Ensures compatibility with PHPStan extension configuration.
 * - Loads and executes type assertions from a dedicated fixture file.
 * - Uses PHPUnit DataProvider for parameterized test execution.
 * - Validates type inference for {@see ActiveRecord::getAttribute()} method calls.
 * - Verifies behavior property type resolution and model property precedence.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ActiveRecordGetAttributeDynamicMethodReturnTypeExtensionTest extends TypeInferenceTestCase
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from self::gatherAssertTypes(
            "{$directory}/data/type/ActiveRecordGetAttributeDynamicMethodReturnType.php",
        );
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/extension-test.neon'];
    }

    #[DataProvider('dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
