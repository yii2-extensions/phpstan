<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\widget;

use yii\base\Widget;
use yii2\extensions\phpstan\tests\support\createobject\TypedValue;

/**
 * Widget with typed public properties, a setter, a getter-only name and a protected property, whose `run()` returns the
 * created class.
 */
class TargetWidget extends Widget
{
    public int $count = 0;
    public string $name = '';
    public static string $shared = '';
    public TypedValue|null $value = null;

    protected string $internal = '';

    private string $label = '';

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getSize(): int
    {
        return 0;
    }

    public function run(): string
    {
        return static::class;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }
}
