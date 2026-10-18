<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii\db\ActiveRecord;
use yii2\extensions\phpstan\tests\provider\ActiveRecordQueryDynamicStaticMethodReturnTypeExtensionProvider;
use yii2\extensions\phpstan\type\ActiveRecordQueryDynamicStaticMethodReturnTypeExtension;

/**
 * Test suite for {@see ActiveRecordQueryDynamicStaticMethodReturnTypeExtension} binding the rows of a non-generic
 * custom query class returned by an {@see ActiveRecord} static method to the called model.
 *
 * Guards the inferred query type through every row-dependent {@see \yii\db\ActiveQuery} method, the late static
 * binding of `self::find()`, `static::find()`, and `parent::find()`, and the calls that must stay unchanged. The union
 * ordering methods and the generic {@see \yii\db\BatchQueryResult} are asserted only on Yii versions declaring them.
 *
 * {@see ActiveRecordQueryDynamicStaticMethodReturnTypeExtensionProvider} for type assertion datasets.
 */
final class ActiveRecordQueryDynamicStaticMethodReturnTypeExtensionTest extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-test.neon'];
    }

    #[DataProviderExternal(ActiveRecordQueryDynamicStaticMethodReturnTypeExtensionProvider::class, 'dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
