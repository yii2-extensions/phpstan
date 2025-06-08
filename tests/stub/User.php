<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\stub;

use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class User extends ActiveRecord implements IdentityInterface
{
    public static function findIdentity($id)
    {
        return new static();
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return new static();
    }

    public function getId()
    {
        return 1;
    }

    public function getAuthKey()
    {
        return '';
    }

    public function validateAuthKey($authKey)
    {
        return true;
    }
}
