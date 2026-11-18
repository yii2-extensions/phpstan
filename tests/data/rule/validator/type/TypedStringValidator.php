<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\type;

use yii\validators\StringValidator;

/**
 * String validator with a typed option, mapped to `StringValidator` by the analysis container.
 */
final class TypedStringValidator extends StringValidator
{
    public int $limit = 10;
}
