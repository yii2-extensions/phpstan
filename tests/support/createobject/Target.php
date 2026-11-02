<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use yii\base\BaseObject;

/**
 * Configurable target inheriting the constructor and `__set()` of {@see BaseObject}, with one member of each kind.
 *
 * @property string $documented
 * @property string $annotated
 */
class Target extends BaseObject
{
    use SetterTrait;

    public string $name = '';

    public static string $shared = '';

    protected string $internal = '';

    protected static string $registry = '';

    private string $secret = '';

    public function getCount(): int
    {
        return 0;
    }

    public function setLabel(string $label): void
    {
        $this->secret = $label;
    }

    public function snapshot(): string
    {
        $this->setHidden($this->internal);

        return $this->secret;
    }

    protected function setGuarded(string $value): void
    {
        $this->internal = $value;
    }

    private function setHidden(string $value): void
    {
        $this->secret = $value;
    }
}
