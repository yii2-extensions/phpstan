<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

use yii\base\BaseObject;

/**
 * Object with a typed `$count` property.
 */
class CountObject extends BaseObject
{
    public int $count = 0;
}
