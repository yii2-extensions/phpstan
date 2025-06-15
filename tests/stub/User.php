<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\stub;

use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User ActiveRecord model for testing property and rule definitions.
 *
 * Provides a minimal {@see ActiveRecord} subclass with explicit property declarations and validation rules for static
 * analysis and type inference tests.
 *
 * This class is used in PHPStan and static analysis scenarios to validate correct type inference for property access
 * and rule configuration in Yii Active Record models.
 *
 * @property string $name
 * @property string $email
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class User extends ActiveRecord implements IdentityInterface
{
    public static function findIdentity($id)
    {
        return YII_ENV_DEV ? new self() : null;
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return YII_ENV_DEV ? new self() : null;
    }

    public function getId()
    {
        return YII_ENV_DEV ? 'dev-id' : 1;
    }

    public function getAuthKey()
    {
        return YII_ENV_DEV ? 'dev-auth' : null;
    }

    public function validateAuthKey($authKey)
    {
        return true;
    }

    public static function tableName(): string
    {
        return 'users';
    }

    public function rules(): array
    {
        return [
            [['id'], 'integer'],
            [['name', 'email'], 'string'],
            [['email'], 'email'],
        ];
    }
}
