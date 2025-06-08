<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\stub;

use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class User extends ActiveRecord implements IdentityInterface
{
    public static function findIdentity($id): IdentityInterface
    {
        return new static();
    }

    public static function findIdentityByAccessToken($token, $type = null): IdentityInterface
    {
        return new static();
    }

    public function getId(): int
    {
        return 1;
    }

    public function getAuthKey(): string
    {
        return '';
    }

    public function validateAuthKey($authKey): bool
    {
        return true;
    }
}
