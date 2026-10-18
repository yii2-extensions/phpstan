<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\property;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\tests\provider\UserPropertiesClassReflectionExtensionProvider;

/**
 * Unit tests for {@see UserPropertiesClassReflectionExtension} property type inference for the Yii User component.
 *
 * {@see UserPropertiesClassReflectionExtensionProvider} for type assertion datasets.
 */
final class UserPropertiesClassReflectionExtensionTest extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-test.neon'];
    }

    #[DataProviderExternal(UserPropertiesClassReflectionExtensionProvider::class, 'dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
