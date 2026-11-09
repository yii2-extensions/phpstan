<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

/**
 * Class without `attach()` whose own `__call()` accepts any method, so that Yii's call succeeds.
 */
class MagicCallClass
{
    /**
     * @param array<array-key, mixed> $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        return null;
    }
}
