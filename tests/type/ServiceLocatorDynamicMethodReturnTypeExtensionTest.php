<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use yii\di\ServiceLocator;

/**
 * Test suite for type inference of dynamic method return types in {@see ServiceLocator} for Yii component scenarios.
 *
 * Validates that PHPStan correctly infers types for dynamic ServiceLocator method calls and result sets in custom
 * {@see ServiceLocator} usage, using fixture-based assertions for component resolution, dependency injection, and
 * property access.
 *
 * The test class loads type assertions from a fixture file and delegates checks to the parent
 * {@see TypeInferenceTestCase}, ensuring that extension logic for {@see ServiceLocator} dynamic method return types is
 * robust and consistent with expected behavior.
 *
 * Key features:
 * - Ensures compatibility with PHPStan extension configuration.
 * - Loads and executes type assertions from a dedicated fixture file.
 * - Uses PHPUnit DataProvider for parameterized test execution.
 * - Validates type inference for chained query methods and result types.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ServiceLocatorDynamicMethodReturnTypeExtensionTest extends TypeInferenceTestCase
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from self::gatherAssertTypes(
            "{$directory}/data/type/ServiceLocatorDynamicMethodReturnType.php",
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
