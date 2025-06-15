<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\fixture\data\property;

use Yii;
use yii\base\InvalidConfigException;
use yii\web\{Application, Session};
use yii2\extensions\phpstan\tests\stub\MyActiveRecord;

use function PHPStan\Testing\assertType;

/**
 * Data provider for property and method type reflection of Yii Web Application in PHPStan analysis.
 *
 * Validates type inference and return types for properties and methods accessed on {@see Application}, ensuring that
 * PHPStan correctly recognizes and infers types for native, virtual, and custom components as used in the web
 * application context.
 *
 * These tests cover scenarios including direct property access, array access, chained property/method access, and
 * assignment to global `Yii::$app`, verifying that type assertions match the expected return types for each case.
 *
 * Key features.
 * - Assignment and type assertion for global `Yii::$app`.
 * - Covers array access and method call scenarios on components.
 * - Ensures compatibility with PHPStan property reflection for Yii Web Application.
 * - Type assertion for native, virtual, and custom component properties.
 * - Validates correct type inference for chained property/method access.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ApplicationWebPropertiesClassReflectionType
{
    /**
     * @throws InvalidConfigException
     */
    public function testReturnApplicationInstanceFromConstructor(): void
    {
        assertType(Application::class, new Application(['id' => 'testApp']));
        assertType('yii\console\Application|yii\web\Application', Yii::$app);
    }

    /**
     * @throws InvalidConfigException
     */
    public function testReturnBoolFromCustomComponentFlagProperty(): void
    {
        $app = new Application(['id' => 'testApp']);

        assertType('bool', $app->customComponent->flag);
        assertType('bool', Yii::$app->customComponent->flag);
    }

    /**
     * @throws InvalidConfigException
     */
    public function testReturnBoolFromCustomComponentSaveMethod(): void
    {
        $app = new Application(['id' => 'testApp']);

        assertType('bool', $app->customComponent->save());
        assertType('bool', Yii::$app->customComponent->save());
    }

    /**
     * @throws InvalidConfigException
     */
    public function testReturnMixedFromCustomComponentFlagProperty(): void
    {
        $app = new Application(['id' => 'testApp']);

        assertType('mixed', $app->customComponent['flag']);
        assertType('mixed', Yii::$app->customComponent['flag']);
    }

    /**
     * @throws InvalidConfigException
     */
    public function testReturnMixedWhenComplexChainedAccess(): void
    {
        $app = new Application(['id' => 'testApp']);

        assertType('string', $app->response->format);
        assertType('string', $app->request->method);
        assertType('string', Yii::$app->request->method);
        assertType('string', Yii::$app->response->format);
    }

    /**
     * @throws InvalidConfigException
     */
    public function testReturnMyActiveRecordFromCustomComponent(): void
    {
        $app = new Application(['id' => 'testApp']);

        assertType(MyActiveRecord::class, $app->customComponent);
        assertType(MyActiveRecord::class, Yii::$app->customComponent);
    }

    /**
     * @throws InvalidConfigException
     */
    public function testReturnMyActiveRecordFormCustomInitializedComponent(): void
    {
        $app = new Application(['id' => 'testApp']);

        assertType(MyActiveRecord::class, $app->customInitializedComponent);
        assertType(MyActiveRecord::class, Yii::$app->customInitializedComponent);
    }

    /**
     * @throws InvalidConfigException
     */
    public function testReturnSessionAndIdPropertyFromApplicationWeb(): void
    {
        $app = new Application(['id' => 'testApp']);

        assertType(Session::class, $app->session);
        assertType('string', $app->id);
    }

    /**
     * @throws InvalidConfigException
     */
    public function testReturnSessionGetAndGetFlashFromApplicationWeb(): void
    {
        $app = new Application(['id' => 'testApp']);

        $sessionData = $app->session->get('user_data');
        $flashMessage = $app->session->getFlash('success');

        assertType('mixed', $sessionData);
        assertType('mixed', $flashMessage);
    }

    /**
     * @throws InvalidConfigException
     */
    public function testReturnStringFromIdProperty(): void
    {
        $app = new Application(['id' => 'testApp']);

        assertType('string', $app->id);
        assertType('string', Yii::$app->id);
    }
}
