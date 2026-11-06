<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Redeclares an inherited protected `$secret` as public.
 */
trait PublicSecretTrait
{
    public mixed $secret = null;
}
