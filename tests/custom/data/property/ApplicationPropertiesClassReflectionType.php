<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\custom\data\property;

use Yii;
use yii\base\Application as BaseApplication;
use yii\console\Application as ConsoleApplication;
use yii\i18n\Formatter;
use yii\web\Application as WebApplication;
use yii2\extensions\phpstan\tests\support\stub\{ApplicationCustom, MyComponent};

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for {@see ApplicationCustom} property reflection via `Yii::$app` in PHPStan analysis.
 *
 * Verifies that `Yii::$app` resolves to the configured custom application type when a user-defined application class is
 * specified in the PHPStan configuration, including virtual properties, and that its configured components apply only
 * to related application receivers.
 */
final class ApplicationPropertiesClassReflectionType
{
    public function testReturnConsoleRequestFromConsoleApplicationReceiver(ConsoleApplication $app): void
    {
        assertType('yii\console\Request', $app->request);
    }

    public function testReturnCustomApplicationInstanceFromYiiApp(): void
    {
        assertType(ApplicationCustom::class, Yii::$app);
    }

    public function testReturnErrorFromClasslessComponentOfConsoleApplicationReceiver(ConsoleApplication $app): void
    {
        assertType('*ERROR*', $app->classlessComponent);
    }

    public function testReturnErrorFromResolvedComponentOfConsoleApplicationReceiver(ConsoleApplication $app): void
    {
        assertType('*ERROR*', $app->resolvedComponent);
    }

    public function testReturnErrorFromUnresolvedComponentOfConsoleApplicationReceiver(ConsoleApplication $app): void
    {
        assertType('*ERROR*', $app->unresolvedComponent);
    }

    public function testReturnErrorFromVirtualPropertyOfConsoleApplicationReceiver(ConsoleApplication $app): void
    {
        assertType('*ERROR*', $app->virtualProperty);
    }

    public function testReturnFormatterFromConsoleApplicationReceiver(ConsoleApplication $app): void
    {
        // the unresolved `formatter` definition belongs to the selected application, so the annotation applies
        assertType(Formatter::class, $app->formatter);
    }

    public function testReturnObjectFromClasslessComponent(): void
    {
        assertType('object', Yii::$app->classlessComponent);
    }

    public function testReturnObjectFromClasslessComponentOfWebApplicationReceiver(WebApplication $app): void
    {
        assertType('object', $app->classlessComponent);
    }

    public function testReturnObjectFromUnresolvedComponentOfWebApplicationReceiver(WebApplication $app): void
    {
        assertType('object', $app->unresolvedComponent);
    }

    public function testReturnObjectFromUnresolvedCoreComponentOfWebApplicationReceiver(WebApplication $app): void
    {
        assertType('object', $app->formatter);
    }

    public function testReturnResolvedComponentFromBaseApplicationReceiver(BaseApplication $app): void
    {
        assertType(MyComponent::class, $app->resolvedComponent);
    }

    public function testReturnResolvedComponentFromWebApplicationReceiver(WebApplication $app): void
    {
        assertType(MyComponent::class, $app->resolvedComponent);
    }

    public function testReturnStringFromCharsetProperty(): void
    {
        assertType('string', Yii::$app->charset);
    }

    public function testReturnStringFromDefaultRouteProperty(): void
    {
        assertType('string', Yii::$app->defaultRoute);
    }

    public function testReturnStringFromLanguageProperty(): void
    {
        assertType('string', Yii::$app->language);
    }

    public function testReturnStringFromVirtualProperty(): void
    {
        assertType('string', Yii::$app->virtualProperty);
    }

    public function testReturnStringFromVirtualPropertyOfBaseApplicationReceiver(BaseApplication $app): void
    {
        assertType('string', $app->virtualProperty);
    }

    public function testReturnStringFromVirtualPropertyOfCustomApplicationReceiver(ApplicationCustom $app): void
    {
        assertType('string', $app->virtualProperty);
    }

    public function testReturnStringFromVirtualPropertyOfWebApplicationReceiver(WebApplication $app): void
    {
        assertType('string', $app->virtualProperty);
    }
}
