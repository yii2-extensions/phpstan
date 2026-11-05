<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\action;

use yii\base\Action;
use yii2\extensions\phpstan\tests\support\createobject\TypedValue;

/**
 * Action with typed public properties, a setter, a getter-only name and a protected property, whose `run()` returns
 * the created class.
 */
class TargetAction extends Action
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
