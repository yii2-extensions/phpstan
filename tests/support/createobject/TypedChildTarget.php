<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

/**
 * Configurable target that inherits every member of {@see TypedTarget}, widens one setter and adds a property.
 */
final class TypedChildTarget extends TypedTarget
{
    public float $weight = 0.0;

    /**
     * @param int|array<mixed> $limit
     */
    public function setLimitTo(int|array $limit): void
    {
        $this->got = $limit;
    }
}
