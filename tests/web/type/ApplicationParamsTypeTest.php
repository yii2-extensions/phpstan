<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\web\type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\tests\provider\WebApplicationParamsTypeProvider;
use yii2\extensions\phpstan\type\ApplicationTypeNodeResolverExtension;

/**
 * Unit tests for the stub-declared `Yii::$app` and `Module::$params` types resolved by
 * {@see ApplicationTypeNodeResolverExtension} with a web application and params.
 *
 * {@see WebApplicationParamsTypeProvider} for type assertion datasets.
 */
final class ApplicationParamsTypeTest extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__, 2) . '/support/extension-params-test.neon'];
    }

    #[DataProviderExternal(WebApplicationParamsTypeProvider::class, 'dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
