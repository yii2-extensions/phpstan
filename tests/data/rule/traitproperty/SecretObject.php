<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

use yii\base\BaseObject;

/**
 * Object with a protected `$secret` property.
 */
class SecretObject extends BaseObject
{
    protected mixed $secret = null;
}
