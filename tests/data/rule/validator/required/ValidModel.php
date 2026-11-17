<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\required;

use ArrayIterator;
use yii\base\Model;
use yii\validators\{FilterValidator, RangeValidator, RegularExpressionValidator};

/**
 * Model whose rules supply each required option, or create validators that require none.
 */
final class ValidModel extends Model
{
    /**
     * @return list<array<array-key, mixed>>
     */
    public function rules(): array
    {
        return [
            ['status', 'in', 'range' => []],
            ['status', 'in', 'range' => [1, 2]],
            ['status', 'in', 'range' => static fn(): array => [1]],
            ['status', RangeValidator::class, 'range' => new ArrayIterator([1])],
            ['code', 'match', 'pattern' => ''],
            ['code', RegularExpressionValidator::class, 'pattern' => '/^[a-z]+$/'],
            ['name', 'filter', 'filter' => 'trim'],
            ['name', FilterValidator::class, 'filter' => static fn(mixed $value): mixed => $value],
            ['status', 'in', '__class' => SubRangeValidator::class],
            ['status', SubRangeValidator::class],
            ['status', 'app\validators\ConfiguredRange'],
            ['status', 'app\validators\MappedRange'],
            ['status', 'app\validators\FactoryRange'],
            ['status', static function (): void {}],
            ['status', 'required'],
        ];
    }
}
