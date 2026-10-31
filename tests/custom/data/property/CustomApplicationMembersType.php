<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\custom\data\property;

use Yii;
use yii\web\{Request, Session};
use yii2\extensions\phpstan\tests\support\stub\ApplicationCustomMembers;

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for a custom Web application selected through `phpstan.application_type` that declares its own
 * native and annotated members.
 *
 * Verifies that `Yii::$app` exposes both own members of {@see ApplicationCustomMembers} together with the members and
 * the identity argument it inherits from the Web application.
 */
final class CustomApplicationMembersType
{
    public function testReturnCustomApplicationInstanceFromYiiApp(): void
    {
        assertType(ApplicationCustomMembers::class, Yii::$app);
    }

    public function testReturnInheritedIdentityUserFromUserProperty(): void
    {
        assertType('yii\web\User<yii\web\IdentityInterface>', Yii::$app->user);
    }

    public function testReturnIntFromNativeProperty(): void
    {
        assertType('int', Yii::$app->nativeProperty);
    }

    public function testReturnRequestFromInheritedRequestProperty(): void
    {
        assertType(Request::class, Yii::$app->request);
    }

    public function testReturnSessionFromInheritedSessionProperty(): void
    {
        assertType(Session::class, Yii::$app->session);
    }

    public function testReturnStringFromAnnotatedProperty(): void
    {
        assertType('string', Yii::$app->annotatedProperty);
    }

    public function testReturnStringFromInheritedDefaultRouteProperty(): void
    {
        assertType('string', Yii::$app->defaultRoute);
    }
}
