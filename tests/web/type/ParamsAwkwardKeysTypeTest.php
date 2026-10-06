<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\web\type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use yii2\extensions\phpstan\type\ApplicationTypeNodeResolverExtension;

/**
 * Unit tests for the params shape resolved by {@see ApplicationTypeNodeResolverExtension} for keys that need quoting
 * or escaping when written as a PHPDoc array shape.
 */
final class ParamsAwkwardKeysTypeTest extends TypeInferenceTestCase
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from self::gatherAssertTypes("{$directory}/data/type/ParamsAwkwardKeysType.php");
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__, 2) . '/support/extension-awkward-params-test.neon'];
    }

    #[DataProvider('dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
