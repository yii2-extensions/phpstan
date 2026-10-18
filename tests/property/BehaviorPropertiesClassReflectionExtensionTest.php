<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\property;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\tests\provider\BehaviorPropertiesClassReflectionExtensionProvider;

/**
 * Unit tests for {@see BehaviorPropertiesClassReflectionExtension} property type inference for attached Yii behaviors.
 *
 * {@see BehaviorPropertiesClassReflectionExtensionProvider} for type assertion datasets.
 */
final class BehaviorPropertiesClassReflectionExtensionTest extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-test.neon'];
    }

    #[DataProviderExternal(BehaviorPropertiesClassReflectionExtensionProvider::class, 'dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
