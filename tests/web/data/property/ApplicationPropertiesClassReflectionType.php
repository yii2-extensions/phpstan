<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\web\fixture\data\property;

use Yii;
use yii\db\Connection;
use yii\i18n\{Formatter, I18N};
use yii\log\Dispatcher;
use yii\mail\MailerInterface;
use yii\web\{
    Application,
    AssetManager,
    Controller,
    ErrorHandler,
    Request,
    Response,
    Session,
    UrlManager,
    User,
    View,
};

use function PHPStan\Testing\assertType;

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

    public function testReturnStringFromCharsetProperty(): void
    {
        assertType('string', Yii::$app->charset);
    }

    public function testReturnControllerFromProperty(): void
    {
        assertType(Controller::class, Yii::$app->controller);
    }

    public function testReturnDbFromComponent(): void
    {
        assertType(Connection::class, Yii::$app->db);
    }

    public function testReturnStringFromDefaultRouteProperty(): void
    {
        assertType('string', Yii::$app->defaultRoute);
    }

    public function testReturnErrorHandlerFromComponent(): void
    {
        assertType(ErrorHandler::class, Yii::$app->errorHandler);
    }

    public function testReturnFormatterFromComponent(): void
    {
        assertType(Formatter::class, Yii::$app->formatter);
    }

    public function testReturnStringFromHomeUrlProperty(): void
    {
        assertType('string', Yii::$app->homeUrl);
    }

    public function testReturnI18nFromComponent(): void
    {
        assertType(I18N::class, Yii::$app->i18n);
    }

    public function testReturnStringFromLanguageProperty(): void
    {
        assertType('string', Yii::$app->language);
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
        assertType(User::class, Yii::$app->user);
    }

    public function testReturnViewFromComponent(): void
    {
        assertType(View::class, Yii::$app->view);
    }
}
