<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\web\property;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\tests\provider\WebApplicationParamsPropertyProvider;

/**
 * Unit tests for {@see ApplicationPropertiesClassReflectionExtension} params type inference.
 *
 * Validates that PHPStan correctly infers array shape types for `Yii::$app->params` when configured via the extension
 * configuration file.
 *
 * {@see WebApplicationParamsPropertyProvider} for type assertion datasets.
 */
final class ApplicationParamsTypeTest extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__, 2) . '/support/extension-params-test.neon'];
    }

    #[DataProviderExternal(WebApplicationParamsPropertyProvider::class, 'dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
