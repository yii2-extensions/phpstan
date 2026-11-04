<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use Closure;
use yii\base\BaseObject;

/**
 * Configurable target inheriting the constructor and `__set()` of {@see BaseObject}, with natively typed members.
 */
class TypedTarget extends BaseObject
{
    use TypedSetterTrait;

    public int $__class = 0;
    public int $both = 0;
    public TypedContract|null $contract = null;
    public int $count = 0;
    /**
     * @var non-empty-string
     */
    public string $documented = 'documented';
    public bool $enabled = false;
    public readonly int $frozen;
    public mixed $got = null;
    /**
     * @var array<mixed>
     */
    public array $items = [];
    public int|string $key = 0;
    public int|null $limit = null;
    public float $ratio = 0.0;
    /**
     * @var iterable<mixed>
     */
    public iterable $rows = [];
    public static int $shared = 0;
    public TypedSuit $suit = TypedSuit::Hearts;
    public string $title = '';
    public mixed $untyped = null;
    public TypedValue|null $value = null;

    protected int $guardedCount = 0;

    public function init(): void
    {
        parent::init();

        $this->frozen = 1;
    }

    public function set(int $value): void
    {
        $this->got = $value;
    }

    public function setAnything(mixed $value): void
    {
        $this->got = $value;
    }

    /**
     * @param array<mixed> $both
     */
    public function setBoth(array $both): void
    {
        $this->got = $both;
    }

    public function setClass(int $class): void
    {
        $this->got = $class;
    }

    public static function setDefaultCount(int $count): void
    {
        self::$shared = $count;
    }

    public function setDisabled(false $disabled): void
    {
        $this->got = $disabled;
    }

    /**
     * @param Closure(): mixed $factory
     */
    public function setFactory(Closure $factory): void
    {
        $this->got = $factory;
    }

    public function setFlag(bool $flag): void
    {
        $this->got = $flag;
    }

    /**
     * @param array<mixed> $guardedCount
     */
    public function setGuardedCount(array $guardedCount): void
    {
        $this->got = $guardedCount;
    }

    /**
     * @param callable(): mixed $handler
     */
    public function setHandler(callable $handler): void
    {
        $this->got = $handler;
    }

    public function setLabel(string|null $label): void
    {
        $this->got = $label;
    }

    public function setLimitTo(int $limit): void
    {
        $this->limit = $limit;
    }

    public function setNothing(): void
    {
        $this->got = null;
    }

    public function setPair(int $first, int $second): void
    {
        $this->got = [$first, $second];
    }

    /**
     * @param array<mixed> $shared
     */
    public function setShared(array $shared): void
    {
        $this->got = $shared;
    }

    /**
     * @param positive-int $size
     */
    public function setSize(int $size): void
    {
        $this->got = $size;
    }

    /**
     * @param array<mixed> $tags
     */
    public function setTags(array $tags): void
    {
        $this->got = $tags;
    }

    public function setText(string $text): void
    {
        $this->got = $text;
    }

    public function setValues(int ...$values): void
    {
        $this->got = $values;
    }

    public function snapshot(): int
    {
        $this->setSecret($this->guardedCount);

        return $this->guardedCount;
    }

    private function setSecret(int $secret): void
    {
        $this->guardedCount = $secret;
    }
}
