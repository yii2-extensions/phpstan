<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\custom\property;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\tests\provider\CustomApplicationMembersProvider;

/**
 * Unit tests for the stub-declared `Yii::$app` type and {@see ApplicationPropertiesClassReflectionExtension} property
 * inference of a custom Web application with its own native and annotated members.
 *
 * {@see CustomApplicationMembersProvider} for type assertion datasets.
 */
final class CustomApplicationMembersTest extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/extension-custom-members-test.neon'];
    }

    #[DataProviderExternal(CustomApplicationMembersProvider::class, 'dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
