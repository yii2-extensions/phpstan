<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\custom\property;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\tests\provider\CustomApplicationPropertiesClassReflectionExtensionProvider;

/**
 * Unit tests for {@see ApplicationPropertiesClassReflectionExtension} property type inference (custom application
 * context).
 *
 * {@see CustomApplicationPropertiesClassReflectionExtensionProvider} for type assertion datasets.
 */
final class ApplicationPropertiesClassReflectionExtensionTest extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/extension-custom-test.neon'];
    }

    #[DataProviderExternal(CustomApplicationPropertiesClassReflectionExtensionProvider::class, 'dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
