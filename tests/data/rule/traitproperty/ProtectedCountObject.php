<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

use yii\base\BaseObject;

/**
 * Object whose protected `$count` comes from {@see ProtectedCountTrait}.
 */
class ProtectedCountObject extends BaseObject
{
    use ProtectedCountTrait;
}
