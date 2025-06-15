<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\fixture\data\property;

use Yii;
use yii\base\InvalidConfigException;
use yii\console\Application;
use yii2\extensions\phpstan\tests\stub\MyActiveRecord;

use function PHPStan\Testing\assertType;

/**
 * Data provider for property and method type reflection of Yii console Application in PHPStan analysis.
 *
 * Validates type inference and return types for properties and methods accessed on {@see Application}, ensuring that
 * PHPStan correctly recognizes and infers types for native, virtual, and custom component properties as used in the
 * console application context.
 *
 * These tests cover scenarios including direct property access, virtual property access, chained property/method
 * access, and assignment to global `Yii::$app`, verifying that type assertions match the expected return types for each
 * case.
 *
 * Key features.
 * - Assignment and type assertion for global `Yii::$app`.
 * - Covers chained property/method access and array-style property access.
 * - Ensures compatibility with PHPStan property and method reflection for Yii console Application classes.
 * - Type assertion for native, virtual, and custom component properties.
 * - Validates correct type inference for all supported property types and method return values.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ApplicationConsolePropertiesClassReflectionType
{
    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnApplicationInstanceFromConstructor(): void
    {
        assertType(Application::class, new Application(['id' => 'testApp']));
        assertType('yii\console\Application|yii\web\Application', Yii::$app);
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnBoolFromCustomComponentFlagProperty(): void
    {
        $app = new Application(['id' => 'testApp']);

        assertType('bool', $app->customComponent->flag);
        assertType('bool', Yii::$app->customComponent->flag);
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnBoolFromCustomComponentSaveMethod(): void
    {
        $app = new Application(['id' => 'testApp']);

        assertType('bool', $app->customComponent->save());
        assertType('bool', Yii::$app->customComponent->save());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnMixedFromCustomComponentFlagProperty(): void
    {
        $app = new Application(['id' => 'testApp']);

        assertType('mixed', $app->customComponent['flag']);
        assertType('mixed', Yii::$app->customComponent['flag']);
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnMyActiveRecordFromCustomComponent(): void
    {
        $app = new Application(['id' => 'testApp']);

        assertType(MyActiveRecord::class, $app->customComponent);
        assertType(MyActiveRecord::class, Yii::$app->customComponent);
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnMyActiveRecordFromCustomInitializedComponent(): void
    {
        $app = new Application(['id' => 'testApp']);

        assertType(MyActiveRecord::class, $app->customInitializedComponent);
        assertType(MyActiveRecord::class, Yii::$app->customInitializedComponent);
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnStringFromIdProperty(): void
    {
        $app = new Application(['id' => 'testApp']);

        assertType('string', $app->id);
        assertType('string', Yii::$app->id);
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnStringWhenComplexChainedAccess(): void
    {
        $app = new Application(['id' => 'testApp']);

        assertType('string', $app->response->format);
        assertType('string', $app->request->method);
        assertType('string', Yii::$app->request->method);
        assertType('string', Yii::$app->response->format);
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     *
     * Session property doesn't exist natively in Yii Console Application.
     */
    public function testSessionPropertyNotAvailableInConsoleApplication(): void
    {
        $app = new Application(['id' => 'testApp']);

        Yii::$app = $app;

        // @phpstan-ignore-next-line property.notFound
        assertType('*ERROR*', $app->session);
        // @phpstan-ignore-next-line property.notFound
        assertType('*ERROR*', Yii::$app->session);

        unset(Yii::$app);
    }
}
