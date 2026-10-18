<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\method;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\tests\provider\BehaviorMethodsClassReflectionExtensionProvider;

/**
 * Unit tests for {@see BehaviorMethodsClassReflectionExtension} method type inference for attached Yii behaviors.
 *
 * {@see BehaviorMethodsClassReflectionExtensionProvider} for type assertion datasets.
 */
final class BehaviorMethodsClassReflectionExtensionTest extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-test.neon'];
    }

    #[DataProviderExternal(BehaviorMethodsClassReflectionExtensionProvider::class, 'dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
