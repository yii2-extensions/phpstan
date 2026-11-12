<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

/**
 * Invocable object that is not a `Closure`, used as a validator type.
 */
final class DefinitionInvocable
{
    public function __invoke(string $attribute): void {}
}
