<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\provider\ServiceMapDefinitionTypeProvider;

/**
 * Tests that components and services defined by closures and class name strings resolve as {@see ServiceMap} registers
 * them, and that definitions whose class can't be determined keep the type declared by Yii.
 *
 * {@see ServiceMapDefinitionTypeProvider} for type assertion datasets.
 */
final class ServiceMapDefinitionTypeTest extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-definition-forms-test.neon'];
    }

    #[DataProviderExternal(ServiceMapDefinitionTypeProvider::class, 'dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
