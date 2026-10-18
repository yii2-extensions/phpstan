<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii\di\ServiceLocator;
use yii2\extensions\phpstan\tests\provider\ServiceLocatorDynamicMethodReturnTypeExtensionProvider;

/**
 * Tests that {@see ServiceLocator::get()} resolves component IDs, service IDs, and class names, with nullability
 * following the `$throwException` conditional return type declared by Yii.
 *
 * {@see ServiceLocatorDynamicMethodReturnTypeExtensionProvider} for type assertion datasets.
 */
final class ServiceLocatorDynamicMethodReturnTypeExtensionTest extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-test.neon'];
    }

    #[DataProviderExternal(ServiceLocatorDynamicMethodReturnTypeExtensionProvider::class, 'dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
