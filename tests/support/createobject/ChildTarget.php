<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

/**
 * Configurable target that inherits every member of {@see Target} and adds a setter.
 */
final class ChildTarget extends Target
{
    public string $colourCode = '';

    public function setShade(string $shade): void
    {
        $this->colourCode = $shade;
    }
}
