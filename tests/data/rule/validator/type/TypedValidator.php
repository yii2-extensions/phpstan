<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\type;

use yii\validators\Validator;

/**
 * Validator whose options have native types, PHPDoc-only types, untyped and `callable` setters, and a read-only property.
 */
class TypedValidator extends Validator
{
    /**
     * @var int
     */
    public $documented;
    public readonly int $frozen;
    public string|null $label = null;
    public int $limit = 10;
    /**
     * @var list<string>
     */
    public array $list = [];
    public bool $strict = false;

    public function setHandler(callable $handler): void {}

    public function setLoose(mixed $value): void {}

    /**
     * @param list<string> $tags
     */
    public function setTags(array $tags): void {}

    protected function validateValue($value): array|null
    {
        return null;
    }
}
