<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\custom\user;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\tests\provider\CustomApplicationConfiguredUserComponentProvider;

/**
 * Unit tests for {@see ApplicationPropertiesClassReflectionExtension} and
 * {@see ServiceLocatorDynamicMethodReturnTypeExtension} type inference of a `user` component configured with a class
 * and an `identityClass` (custom application context).
 *
 * {@see CustomApplicationConfiguredUserComponentProvider} for type assertion datasets.
 */
final class ConfiguredUserComponentTest extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/extension-custom-configured-user-test.neon'];
    }

    #[DataProviderExternal(CustomApplicationConfiguredUserComponentProvider::class, 'dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
