<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use yii\db\ActiveQuery;
use yii2\extensions\phpstan\type\ActiveQueryDynamicMethodReturnTypeExtension;

/**
 * Test suite for {@see ActiveQueryDynamicMethodReturnTypeExtension} inference of {@see ActiveQuery::asArray()} rows.
 *
 * Validates that `asArray()` replaces the query's `T` with an array shape built from the model's `@property` tags,
 * keeps the model for `false`, and unions both for a non-constant argument. Generic custom query classes are kept,
 * while non-generic ones fall back to {@see ActiveQuery} once rows become arrays; `one()`, `all()`, and fluent methods
 * resolve through Yii's native generic PHPDoc, or through the model derived from the `one()` and `all()` overrides of
 * a non-generic query class whose `T` names no model.
 */
final class ActiveQueryDynamicReturnTypeExtensionTest extends TypeInferenceTestCase
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from self::gatherAssertTypes(
            "{$directory}/data/type/ActiveQueryDynamicMethodReturnType.php",
        );
        yield from self::gatherAssertTypes(
            "{$directory}/data/type/ActiveQueryDerivedModelReturnType.php",
        );
        yield from self::gatherAssertTypes(
            "{$directory}/data/type/ActiveQueryDerivedModelEdgeType.php",
        );
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-test.neon'];
    }

    #[DataProvider('dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
