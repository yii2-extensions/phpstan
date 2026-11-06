<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\assignment;

use yii\base\BaseObject;

/**
 * Configurable target with a set hook and asymmetric visibility properties beside a plain one.
 */
final class VisibilityHookTarget extends BaseObject
{
    public int $hooked = 0 {
        set {
            $this->hooked = $value;
        }
    }
    public int $plain = 0;
    public private(set) int $privateSet = 0;
    public protected(set) int $protectedSet = 0;
}
