<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\web\data\property;

use Yii;
use yii\base\Application as BaseApplication;
use yii\console\Application as ConsoleApplication;
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
use yii2\extensions\phpstan\tests\support\stub\{ApplicationCustom, ApplicationDbSession};

use function PHPStan\Testing\{assertSuperType, assertType};

/**
 * Type assertion fixture for {@see Application} property reflection via `Yii::$app` in PHPStan analysis.
 *
 * Verifies type inference for core web application properties and components, including native, interface, nullable,
 * and union-typed members, and that configured components apply only to related application receivers.
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

    public function testReturnBaseViewFromConsoleApplicationReceiver(ConsoleApplication $app): void
    {
        // the configured `yii\web\View` override belongs to the selected Web application
        assertType('yii\base\View', $app->view);
    }

    public function testReturnCacheFromComponent(): void
    {
        assertType('yii\caching\CacheInterface|null', Yii::$app->cache);
    }

    public function testReturnConfiguredUserFromCustomApplicationReceiver(ApplicationCustom $app): void
    {
        assertType('yii\web\User<yii2\extensions\phpstan\tests\support\stub\User>', $app->user);
    }

    public function testReturnConsoleRequestFromConsoleApplicationReceiver(ConsoleApplication $app): void
    {
        assertType('yii\console\Request', $app->request);
    }

    public function testReturnControllerFromProperty(): void
    {
        // Yii 2.0.54 describes the generic controller as `Controller<yii\base\Module>`, later versions omit the default
        assertSuperType('yii\web\Controller|null', Yii::$app->controller);
        assertType('bool', Yii::$app->controller === null);
    }

    public function testReturnDbFromComponent(): void
    {
        assertType(Connection::class, Yii::$app->db);
    }

    public function testReturnDbSessionFromNarrowedApplicationReceiver(ApplicationDbSession $app): void
    {
        // the receiver's narrower annotation must not be replaced by the selected application's `yii\web\Session`
        assertType('yii\web\DbSession', $app->session);
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

    public function testReturnStringFromVirtualPropertyOfCustomApplicationReceiver(ApplicationCustom $app): void
    {
        assertType('string', $app->virtualProperty);
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

    public function testReturnWebRequestFromBaseApplicationReceiver(BaseApplication $app): void
    {
        assertType(Request::class, $app->request);
    }

    public function testReturnWebViewFromBaseApplicationReceiver(BaseApplication $app): void
    {
        assertType(View::class, $app->view);
    }
}
