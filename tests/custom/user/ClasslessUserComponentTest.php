<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\custom\user;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\tests\provider\CustomApplicationClasslessUserComponentProvider;

/**
 * Unit tests for {@see ApplicationPropertiesClassReflectionExtension} and
 * {@see ServiceLocatorDynamicMethodReturnTypeExtension} type inference of a `user` component configured without a
 * class (custom application context).
 *
 * {@see CustomApplicationClasslessUserComponentProvider} for type assertion datasets.
 */
final class ClasslessUserComponentTest extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/extension-custom-classless-user-test.neon'];
    }

    #[DataProviderExternal(CustomApplicationClasslessUserComponentProvider::class, 'dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
