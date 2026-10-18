<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\console\property;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\tests\provider\ConsoleApplicationPropertiesClassReflectionExtensionProvider;

/**
 * Unit tests for {@see ApplicationPropertiesClassReflectionExtension} property type inference (Console application
 * context).
 *
 * {@see ConsoleApplicationPropertiesClassReflectionExtensionProvider} for type assertion datasets.
 */
final class ApplicationPropertiesClassReflectionExtensionTest extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/extension-console-test.neon'];
    }

    #[DataProviderExternal(ConsoleApplicationPropertiesClassReflectionExtensionProvider::class, 'dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
