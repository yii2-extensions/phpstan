<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use yii\BaseYii;
use yii\web\IdentityInterface;

/**
 * Subclass of {@see BaseYii} that overrides `createObject()`.
 *
 * @extends BaseYii<IdentityInterface>
 */
final class OverridingFactory extends BaseYii
{
    /**
     * @param array<array-key, mixed> $params
     */
    public static function createObject($type, array $params = []): object
    {
        return new Target();
    }
}
