<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii\db\ActiveRecord;
use yii2\extensions\phpstan\tests\provider\ActiveRecordStaticMethodReturnTypeProvider;

/**
 * Test suite for type inference of {@see ActiveRecord} static query methods such as {@see ActiveRecord::find()} and
 * {@see ActiveRecord::findOne()}.
 *
 * Guards the query and result types that PHPStan infers natively from Yii's generic PHPDoc, including calls through a
 * `class-string` variable, `static::find()`, and `self::find()`, and their chaining with the
 * {@see \yii\db\ActiveQuery::asArray()} array shape inference provided by this extension.
 *
 * {@see ActiveRecordStaticMethodReturnTypeProvider} for type assertion datasets.
 */
final class ActiveRecordStaticMethodReturnTypeTest extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-test.neon'];
    }

    #[DataProviderExternal(ActiveRecordStaticMethodReturnTypeProvider::class, 'dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
