<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\console\data\property;

use Yii;
use yii\base\{Security, View};
use yii\console\{Application, ErrorHandler, Request, Response};
use yii\db\Connection;
use yii\i18n\{Formatter, I18N};
use yii\log\Dispatcher;
use yii\mail\MailerInterface;
use yii\web\{AssetManager, UrlManager};

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for {@see Application} property reflection via `Yii::$app` in PHPStan analysis.
 *
 * Verifies type inference for core console application properties and components, including native, interface,
 * nullable, and union-typed members.
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

    public function testReturnAuthManagerFromComponent(): void
    {
        assertType('yii\rbac\ManagerInterface|null', Yii::$app->authManager);
    }

    public function testReturnBoolFromEnableCoreCommandsProperty(): void
    {
        assertType('bool', Yii::$app->enableCoreCommands);
    }

    public function testReturnCacheFromComponent(): void
    {
        assertType('yii\caching\CacheInterface|null', Yii::$app->cache);
    }

    public function testReturnControllerFromProperty(): void
    {
        assertType('yii\console\Controller|null', Yii::$app->controller);
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

    public function testReturnSecurityFromComponent(): void
    {
        assertType(Security::class, Yii::$app->security);
    }

    public function testReturnStringFromBasePathProperty(): void
    {
        assertType('string', Yii::$app->basePath);
    }

    public function testReturnStringFromCharsetProperty(): void
    {
        assertType('string', Yii::$app->charset);
    }

    public function testReturnStringFromControllerNamespaceProperty(): void
    {
        assertType('string', Yii::$app->controllerNamespace);
    }

    public function testReturnStringFromDefaultRouteProperty(): void
    {
        assertType('string', Yii::$app->defaultRoute);
    }

    public function testReturnStringFromLanguageProperty(): void
    {
        assertType('string', Yii::$app->language);
    }

    public function testReturnStringFromNameProperty(): void
    {
        assertType('string', Yii::$app->name);
    }

    public function testReturnStringFromRuntimePathProperty(): void
    {
        assertType('string', Yii::$app->runtimePath);
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

    public function testReturnStringFromVendorPathProperty(): void
    {
        assertType('string', Yii::$app->vendorPath);
    }

    public function testReturnUrlManagerFromComponent(): void
    {
        assertType(UrlManager::class, Yii::$app->urlManager);
    }

    public function testReturnViewFromComponent(): void
    {
        assertType(View::class, Yii::$app->view);
    }
}
