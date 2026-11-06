<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Subclass of {@see SecretObject} whose `$secret` is public through {@see PublicSecretTrait}.
 */
final class PublicSecretObject extends SecretObject
{
    use PublicSecretTrait;
}
