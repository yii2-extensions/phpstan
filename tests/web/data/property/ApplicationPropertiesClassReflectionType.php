<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\web\data\property;

use Yii;
use yii\db\Connection;
use yii\i18n\{Formatter, I18N};
use yii\log\Dispatcher;
use yii\mail\MailerInterface;
use yii\web\{
    Application,
    AssetManager,
    ErrorHandler,
    Request,
    Response,
    Session,
    UrlManager,
    View,
};

use function PHPStan\Testing\assertType;

/**
 * Data provider for property reflection of Yii Application properties in PHPStan analysis.
 *
 * Validates type inference and return types for core properties and components of {@see Application} via `Yii::$app`,
 * ensuring that PHPStan correctly recognizes and infers types for all supported application level properties as if they
 * were natively declared on the application class.
 *
 * These tests cover scenarios including direct property access, component-based properties, nullable and interface
 * typed properties, and type assertions for both native and dynamic attached components, verifying that type assertions
 * match the expected return types for each case.
 *
 * Key features.
 * - Coverage for all documented core application properties and components.
 * - Ensures compatibility with PHPStan property reflection for Yii application context.
 * - Type assertion for native, interface, and nullable properties.
 * - Validates correct type inference for all supported property types, including interfaces and union types.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ApplicationPropertiesClassReflectionType
{
    public function testReturnApplicationInstanceFromYiiApp(): void
    {
        assertType(Application::class, Yii::$app);
    }

    public function testReturnAssetManagerFromComponent(): void
    {
        assertType(AssetManager::class, Yii::$app->assetManager);
    }

    public function testReturnCacheFromComponent(): void
    {
        assertType('yii\caching\CacheInterface|null', Yii::$app->cache);
    }

    public function testReturnControllerFromProperty(): void
    {
        assertType('yii\web\Controller<yii\base\Module>|null', Yii::$app->controller);
    }

    public function testReturnDbFromComponent(): void
    {
        assertType(Connection::class, Yii::$app->db);
    }

    public function testReturnErrorHandlerFromComponent(): void
    {
        assertType(ErrorHandler::class, Yii::$app->errorHandler);
    }

    public function testReturnFormatterFromComponent(): void
    {
        assertType(Formatter::class, Yii::$app->formatter);
    }

    public function testReturnI18nFromComponent(): void
    {
        assertType(I18N::class, Yii::$app->i18n);
    }

    public function testReturnLogFromComponent(): void
    {
        assertType(Dispatcher::class, Yii::$app->log);
    }

    public function testReturnMailerFromComponent(): void
    {
        assertType(MailerInterface::class, Yii::$app->mailer);
    }

    public function testReturnRequestFromComponent(): void
    {
        assertType(Request::class, Yii::$app->request);
    }

    public function testReturnResponseFromComponent(): void
    {
        assertType(Response::class, Yii::$app->response);
    }

    public function testReturnSessionFromComponent(): void
    {
        assertType(Session::class, Yii::$app->session);
    }

    public function testReturnStringFromCharsetProperty(): void
    {
        assertType('string', Yii::$app->charset);
    }

    public function testReturnStringFromDefaultRouteProperty(): void
    {
        assertType('string', Yii::$app->defaultRoute);
    }

    public function testReturnStringFromHomeUrlProperty(): void
    {
        assertType('string', Yii::$app->homeUrl);
    }

    public function testReturnStringFromLanguageProperty(): void
    {
        assertType('string', Yii::$app->language);
    }

    public function testReturnStringFromSourceLanguageProperty(): void
    {
        assertType('string', Yii::$app->sourceLanguage);
    }

    public function testReturnStringFromTimeZoneProperty(): void
    {
        assertType('string', Yii::$app->timeZone);
    }

    public function testReturnStringFromUniqueIdProperty(): void
    {
        assertType('string', Yii::$app->uniqueId);
    }

    public function testReturnUrlManagerFromComponent(): void
    {
        assertType(UrlManager::class, Yii::$app->urlManager);
    }

    public function testReturnUserFromComponent(): void
    {
        assertType('yii\web\User<yii2\extensions\phpstan\tests\support\stub\User>', Yii::$app->user);
    }

    public function testReturnViewFromComponent(): void
    {
        assertType(View::class, Yii::$app->view);
    }
}
