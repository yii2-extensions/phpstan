<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test suite for type inference of dynamic method return types in Yii Behaviors for PHPStan analysis.
 *
 * Validates that PHPStan correctly infers types for dynamic methods provided by attached behaviors on
 * {@see MyComponent}, using fixture-based assertions for direct and behavior-injected dynamic method calls,
 * parameterized methods, and shared method resolution.
 *
 * The test class loads type assertions from a fixture file and delegates checks to the parent
 * {@see TypeInferenceTestCase}, ensuring that extension logic for {@see BehaviorDynamicMethodReturnTypeExtension} is
 * robust and consistent with expected behavior.
 *
 * Key features.
 * - Ensures compatibility with PHPStan extension configuration.
 * - Loads and executes type assertions from a dedicated fixture file.
 * - Uses PHPUnit DataProvider for parameterized test execution.
 * - Validates type inference for native and behavior-provided methods.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class BehaviorDynamicMethodReturnTypeExtensionTest extends TypeInferenceTestCase
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from self::gatherAssertTypes("{$directory}/fixture/data/type/BehaviorDynamicMethodReturnType.php");
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
