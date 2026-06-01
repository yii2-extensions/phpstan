<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\property;

use Yii;

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for {@see \yii\web\User} component property reflection via `Yii::$app->user` in PHPStan
 * analysis.
 *
 * Verifies type inference for user component properties, including identity, guest, and nullable identity members
 * accessed through the null-safe operator.
 */
final class UserPropertiesClassReflectionType
{
    public function testReturnBooleanFromIsGuestProperty(): void
    {
        assertType('bool', Yii::$app->user->isGuest);
    }

    public function testReturnBooleanOrNullFromValidateAuthKeyMethod(): void
    {
        assertType('bool|null', Yii::$app->user->identity?->validateAuthKey('123abc'));
    }

    public function testReturnIdentityFromIdentityProperty(): void
    {
        assertType('yii2\extensions\phpstan\tests\support\stub\User|null', Yii::$app->user->identity);
    }

    public function testReturnStringFromEmailProperty(): void
    {
        assertType('string|null', Yii::$app->user->identity?->email);
    }

    public function testReturnStringFromNameProperty(): void
    {
        assertType('string|null', Yii::$app->user->identity?->name);
    }

    public function testReturnStringFromReturnUrlProperty(): void
    {
        assertType('string', Yii::$app->user->returnUrl);
    }

    public function testReturnStringOrNullFromGetAuthKeyMethod(): void
    {
        assertType('string|null', Yii::$app->user->identity?->getAuthKey());
    }

    public function testReturnUnionFromGetIdMethod(): void
    {
        assertType('int|null', Yii::$app->user->identity?->getId());
    }

    public function testReturnUnionFromIdProperty(): void
    {
        assertType('int|null', Yii::$app->user->identity?->id);
    }
}
