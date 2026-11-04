<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use yii\base\BaseObject;

/**
 * Target bound under its own class name by a definition with constructor params only.
 */
final class ArgumentsTarget extends BaseObject
{
    /**
     * @var array<mixed>|null
     */
    public array|null $list = null;
    public string $name = '';
}
