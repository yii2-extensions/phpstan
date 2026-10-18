<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\web\property;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\tests\provider\WebApplicationPropertiesClassReflectionExtensionProvider;

/**
 * Unit tests for {@see ApplicationPropertiesClassReflectionExtension} property type inference (Web application context).
 *
 * {@see WebApplicationPropertiesClassReflectionExtensionProvider} for type assertion datasets.
 */
final class ApplicationPropertiesClassReflectionExtensionTest extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__, 2) . '/support/extension-test.neon'];
    }

    #[DataProviderExternal(WebApplicationPropertiesClassReflectionExtensionProvider::class, 'dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
