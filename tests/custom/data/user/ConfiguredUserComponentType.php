<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\custom\data\user;

use Yii;
use yii\web\User;
use yii2\extensions\phpstan\tests\support\stub\ApplicationCustom;

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for a custom Web application whose `user` component is configured with a class and an
 * `identityClass`.
 *
 * Verifies that `Yii::$app` keeps the selected {@see ApplicationCustom} class, that the `user` property receives the
 * configured identity argument, and that `get('user')` and `getUser()` keep the types declared without it.
 */
final class ConfiguredUserComponentType
{
    public function testReturnConfiguredIdentityUserFromUserProperty(): void
    {
        assertType('yii\web\User<yii2\extensions\phpstan\tests\support\stub\User>', Yii::$app->user);
    }

    public function testReturnConfiguredIdentityUserFromUserPropertyOfCustomApplicationReceiver(
        ApplicationCustom $app,
    ): void {
        assertType('yii\web\User<yii2\extensions\phpstan\tests\support\stub\User>', $app->user);
    }

    public function testReturnCustomApplicationInstanceFromYiiApp(): void
    {
        assertType(ApplicationCustom::class, Yii::$app);
    }

    public function testReturnInheritedIdentityUserFromGetUserMethod(): void
    {
        assertType('yii\web\User<yii\web\IdentityInterface>', Yii::$app->getUser());
    }

    public function testReturnPlainUserFromGetUserComponent(): void
    {
        assertType(User::class, Yii::$app->get('user'));
    }

    public function testReturnStringFromVirtualProperty(): void
    {
        assertType('string', Yii::$app->virtualProperty);
    }
}
