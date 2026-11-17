<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\required;

use yii\base\Model;
use yii\validators\{FilterValidator, RangeValidator, RegularExpressionValidator};

/**
 * Model whose rules create validators without their required option; `filter()` loses to the alias.
 */
final class ReportedModel extends Model
{
    public function filter(): void {}

    /**
     * @return list<array<array-key, mixed>>
     */
    public function rules(): array
    {
        return [
            ['status', 'in'],
            ['status', 'in', 'range' => null],
            ['status', RangeValidator::class, 'on' => 'inactive'],
            ['code', 'match', 'not' => true],
            ['code', 'match', 'pattern' => null],
            ['code', RegularExpressionValidator::class],
            ['name', 'filter', 'skipOnEmpty' => true],
            ['name', 'filter', 'filter' => null],
            ['name', FilterValidator::class, 'on afterValidate' => static function (): void {}],
            ['status', 'string', '__class' => RangeValidator::class],
            ['status', 'in', 'class' => SubRangeValidator::class],
            ['status', 'app\validators\MessageRange'],
        ];
    }
}
