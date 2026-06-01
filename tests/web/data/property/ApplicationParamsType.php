<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\web\data\property;

use Yii;

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for `Yii::$app->params` type inference in PHPStan analysis.
 *
 * Validates that PHPStan correctly infers array shape types for application params when configured via the extension
 * configuration file.
 */
final class ApplicationParamsType
{
    public function testReturnArrayShapeFromParams(): void
    {
        assertType(
            "array{'turnstile.siteKey': string, adminEmail: string, maxItems: int, debugMode: bool, ratio: float, nullableParam: null, nested: array{key1: string, key2: int}, tags: array{string, string}}",
            Yii::$app->params,
        );
    }

    public function testReturnBoolFromParamsKey(): void
    {
        assertType('bool', Yii::$app->params['debugMode']);
    }

    public function testReturnFloatFromParamsKey(): void
    {
        assertType('float', Yii::$app->params['ratio']);
    }

    public function testReturnIntFromParamsKey(): void
    {
        assertType('int', Yii::$app->params['maxItems']);
    }

    public function testReturnNestedArrayShapeFromParamsKey(): void
    {
        assertType('array{key1: string, key2: int}', Yii::$app->params['nested']);
    }

    public function testReturnNullFromParamsKey(): void
    {
        assertType('null', Yii::$app->params['nullableParam']);
    }

    public function testReturnStringFromDottedParamsKey(): void
    {
        assertType('string', Yii::$app->params['turnstile.siteKey']);
    }

    public function testReturnStringFromParamsKey(): void
    {
        assertType('string', Yii::$app->params['adminEmail']);
    }
}
