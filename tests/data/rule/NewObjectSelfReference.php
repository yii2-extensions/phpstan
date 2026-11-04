<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use yii2\extensions\phpstan\tests\support\createobject\Target;

/**
 * Final configurable class that constructs itself and its parent through `self`, `parent` and `static`.
 */
final class NewObjectSelfReference extends Target
{
    public function closureSelf(): object
    {
        $create = static fn(): object => new self(['colour' => 'red']);

        return $create();
    }

    public function parentClass(): object
    {
        return new parent(['shade' => 'dark']);
    }

    public function selfClass(): object
    {
        return new self(['colour' => 'red']);
    }

    public function selfValid(): object
    {
        return new self(['name' => 'value']);
    }

    public function staticClass(): object
    {
        return new static(['colour' => 'red']);
    }
}
