<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii\di\Container;
use yii2\extensions\phpstan\tests\provider\ContainerDynamicMethodReturnTypeExtensionProvider;

/**
 * Tests that {@see Container::get()} resolves configured service IDs and otherwise keeps Yii's declared return type.
 *
 * {@see ContainerDynamicMethodReturnTypeExtensionProvider} for type assertion datasets.
 */
final class ContainerDynamicMethodReturnTypeExtensionTest extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-test.neon'];
    }

    #[DataProviderExternal(ContainerDynamicMethodReturnTypeExtensionProvider::class, 'dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
