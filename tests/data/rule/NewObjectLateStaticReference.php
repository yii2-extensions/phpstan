<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use yii2\extensions\phpstan\tests\support\createobject\Target;

/**
 * Non-final configurable class whose `static` construction may select a subclass.
 */
class NewObjectLateStaticReference extends Target
{
    public function staticClass(): object
    {
        return new static(['colour' => 'red']);
    }
}
