<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use yii2\extensions\phpstan\tests\support\createobject\Target;

/**
 * First-class callable syntax on `new`, which PHP rejects at compile time; analysed only, never loaded.
 */
final class NewObjectFirstClassCallable
{
    public function create(): mixed
    {
        return new Target(...);
    }
}
