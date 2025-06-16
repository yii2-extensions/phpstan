<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\web\data\property;

use Yii;

use function PHPStan\Testing\assertType;

/**
 * Data provider for property reflection of Yii User component in PHPStan analysis.
 *
 * Validates type inference and return types for properties provided by the Yii User component, ensuring that PHPStan
 * correctly recognizes and infers types for available properties as if they were natively declared on the user object.
 *
 * These tests cover scenarios including direct property access, identity and guest checks, parameterized properties,
 * and shared property resolution, verifying that type assertions match the expected return types for each case.
 *
 * Key features.
 * - Coverage for identity, guest, and parameterized properties.
 * - Ensures compatibility with PHPStan property reflection for Yii user component.
 * - Type assertion for native and user-provided properties.
 * - Validates correct type inference for all supported property types.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class UserPropertiesClassReflectionType
{
    public function testReturnBooleanFromIsGuestProperty(): void
    {
        assertType('bool', Yii::$app->user->isGuest);
    }

    public function testReturnBooleanOrNullFromValidateAuthKeyMethod(): void
    {
        assertType('bool|null', Yii::$app->user->identity->validateAuthKey('123abc'));
    }

    public function testReturnIdentityFromIdentityProperty(): void
    {
        assertType('yii2\extensions\phpstan\tests\stub\User', Yii::$app->user->identity);
    }

    public function testReturnIdentityInterfaceOrNullFromFindIdentityByAccessTokenMethod(): void
    {
        assertType('yii\web\IdentityInterface|null', Yii::$app->user->identity::findIdentityByAccessToken('123abc'));
    }

    public function testReturnIdentityInterfaceOrNullFromFindIdentityMethod(): void
    {
        assertType('yii\web\IdentityInterface|null', Yii::$app->user->identity::findIdentity(1));
    }

    public function testReturnStringFromEmailProperty(): void
    {
        assertType('string', Yii::$app->user->identity->email);
    }

    public function testReturnStringFromNameProperty(): void
    {
        assertType('string', Yii::$app->user->identity->name);
    }

    public function testReturnStringFromReturnUrlProperty(): void
    {
        assertType('string', Yii::$app->user->returnUrl);
    }

    public function testReturnStringOrNullFromGetAuthKeyMethod(): void
    {
        assertType('string|null', Yii::$app->user->identity->getAuthKey());
    }

    public function testReturnUnionFromGetIdMethod(): void
    {
        assertType('int|string|null', Yii::$app->user->getId());
    }

    public function testReturnUnionFromIdProperty(): void
    {
        assertType('int|string|null', Yii::$app->user->id);
    }
}
