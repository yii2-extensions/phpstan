<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\custom\data\user;

use Yii;
use yii\web\Request;
use yii2\extensions\phpstan\tests\support\stub\ApplicationCustom;

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for a custom Web application whose `user` component is configured without a class.
 *
 * Verifies that `Yii::$app` keeps the selected {@see ApplicationCustom} class with its inherited Web and own members,
 * that the `user` property keeps the identity argument inherited by the application class instead of the configured
 * `identityClass`, and that `get('user')` is `object`.
 */
final class ClasslessUserComponentType
{
    public function testReturnCustomApplicationInstanceFromYiiApp(): void
    {
        assertType(ApplicationCustom::class, Yii::$app);
    }

    public function testReturnInheritedIdentityUserFromGetUserMethod(): void
    {
        assertType('yii\web\User<yii\web\IdentityInterface>', Yii::$app->getUser());
    }

    public function testReturnInheritedIdentityUserFromUserProperty(): void
    {
        // `identityClass` is a property of the core `user` component, neither its class nor its identity argument
        assertType('yii\web\User<yii\web\IdentityInterface>', Yii::$app->user);
    }

    public function testReturnInheritedIdentityUserFromUserPropertyOfCustomApplicationReceiver(
        ApplicationCustom $app,
    ): void {
        assertType('yii\web\User<yii\web\IdentityInterface>', $app->user);
    }

    public function testReturnObjectFromGetUserComponent(): void
    {
        assertType('object', Yii::$app->get('user'));
    }

    public function testReturnRequestFromInheritedRequestProperty(): void
    {
        assertType(Request::class, Yii::$app->request);
    }

    public function testReturnStringFromInheritedDefaultRouteProperty(): void
    {
        assertType('string', Yii::$app->defaultRoute);
    }

    public function testReturnStringFromVirtualProperty(): void
    {
        assertType('string', Yii::$app->virtualProperty);
    }
}
