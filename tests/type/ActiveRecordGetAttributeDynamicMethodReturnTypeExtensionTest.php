<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\tests\provider\ActiveRecordGetAttributeDynamicMethodReturnTypeExtensionProvider;
use yii2\extensions\phpstan\tests\support\stub\VirtualAttributeRecord;

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
 * {@see ActiveRecordGetAttributeDynamicMethodReturnTypeExtensionProvider} for type assertion datasets.
 */
final class ActiveRecordGetAttributeDynamicMethodReturnTypeExtensionTest extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-test.neon'];
    }

    #[DataProviderExternal(ActiveRecordGetAttributeDynamicMethodReturnTypeExtensionProvider::class, 'dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }

    public function testGetAttributeDoesNotInvokeVirtualGetters(): void
    {
        $record = new VirtualAttributeRecord();

        self::assertSame(
            'visible',
            $record->label,
            'Property access must invoke the getter.',
        );
        self::assertNull(
            $record->getAttribute('label'),
            'Attribute access must not invoke the read-only getter.',
        );
    }
}
