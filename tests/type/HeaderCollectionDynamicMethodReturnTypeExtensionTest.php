<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii\web\HeaderCollection;
use yii2\extensions\phpstan\tests\provider\HeaderCollectionDynamicMethodReturnTypeExtensionProvider;

/**
 * Tests that {@see HeaderCollection::get()} drops `null` from the return type declared by Yii when the `$default`
 * argument cannot be `null`.
 *
 * {@see HeaderCollectionDynamicMethodReturnTypeExtensionProvider} for type assertion datasets.
 */
final class HeaderCollectionDynamicMethodReturnTypeExtensionTest extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-test.neon'];
    }

    #[DataProviderExternal(HeaderCollectionDynamicMethodReturnTypeExtensionProvider::class, 'dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
