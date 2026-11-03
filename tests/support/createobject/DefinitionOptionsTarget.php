<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use yii\base\BaseObject;

/**
 * Target bound under its own class name by a definition that only sets options, which Yii builds as that class.
 */
final class DefinitionOptionsTarget extends BaseObject
{
    public string $name = '';
}
