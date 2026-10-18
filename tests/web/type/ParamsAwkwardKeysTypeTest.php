<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\web\type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\tests\provider\WebParamsAwkwardKeysTypeProvider;
use yii2\extensions\phpstan\type\ApplicationTypeNodeResolverExtension;

/**
 * Unit tests for the params shape resolved by {@see ApplicationTypeNodeResolverExtension} for keys that need quoting
 * or escaping when written as a PHPDoc array shape.
 *
 * {@see WebParamsAwkwardKeysTypeProvider} for type assertion datasets.
 */
final class ParamsAwkwardKeysTypeTest extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__, 2) . '/support/extension-awkward-params-test.neon'];
    }

    #[DataProviderExternal(WebParamsAwkwardKeysTypeProvider::class, 'dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
