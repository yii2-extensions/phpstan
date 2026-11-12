<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

/**
 * Enum whose cases are objects, used as validator types.
 */
enum DefinitionKind: string
{
    case Required = 'required';
}
