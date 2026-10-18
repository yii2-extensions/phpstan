<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii\db\ActiveRecord;
use yii2\extensions\phpstan\tests\provider\ActiveRecordRelationReturnTypeProvider;

/**
 * Test suite for type inference of {@see ActiveRecord::hasOne()} and {@see ActiveRecord::hasMany()} relation queries.
 *
 * Guards the relation query types that PHPStan infers natively from Yii's generic PHPDoc, including literal,
 * `class-string`, `static::class`, and conditional class arguments, and their chaining with the
 * {@see \yii\db\ActiveQuery::asArray()} array shape inference provided by this extension.
 *
 * {@see ActiveRecordRelationReturnTypeProvider} for type assertion datasets.
 */
final class ActiveRecordRelationReturnTypeTest extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-test.neon'];
    }

    #[DataProviderExternal(ActiveRecordRelationReturnTypeProvider::class, 'dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
