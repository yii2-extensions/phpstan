<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * Stub ActiveRecord model implementing {@see IdentityInterface} for property and rule inference tests.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 */
final class User extends ActiveRecord implements IdentityInterface
{
    public static function findIdentity($id): IdentityInterface|null
    {
        return \defined('YII_ENV_DEV') && YII_ENV_DEV ? new self() : null;
    }

    public static function findIdentityByAccessToken($token, $type = null): IdentityInterface|null
    {
        return \defined('YII_ENV_DEV') && YII_ENV_DEV ? new self() : null;
    }

    public function getAuthKey(): string|null
    {
        return YII_ENV_DEV ? 'dev-auth' : null;
    }

    public function getId(): int
    {
        return 1;
    }

    public function rules(): array
    {
        return [
            [['id'], 'integer'],
            [['name', 'email'], 'string'],
            [['email'], 'email'],
        ];
    }

    public static function tableName(): string
    {
        return 'users';
    }

    public function validateAuthKey($authKey): bool|null
    {
        return YII_ENV_DEV ? true : null;
    }
}
