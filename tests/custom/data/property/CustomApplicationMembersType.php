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
 * Verifies that `Yii::$app` exposes both own members of {@see ApplicationCustomMembers} together with the members it
 * inherits from the Web application, and that the concrete identity bound by its `@extends` tag replaces the default
 * `IdentityInterface` argument.
 */
final class CustomApplicationMembersType
{
    public function testReturnConcreteIdentityUserFromGetUserMethod(): void
    {
        assertType('yii\web\User<yii2\extensions\phpstan\tests\support\stub\User>', Yii::$app->getUser());
    }

    public function testReturnConcreteIdentityUserFromUserProperty(): void
    {
        assertType('yii\web\User<yii2\extensions\phpstan\tests\support\stub\User>', Yii::$app->user);
    }

    public function testReturnCustomApplicationInstanceFromYiiApp(): void
    {
        assertType(ApplicationCustomMembers::class, Yii::$app);
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
