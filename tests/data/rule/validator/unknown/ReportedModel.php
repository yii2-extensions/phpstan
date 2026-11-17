<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\unknown;

use yii\base\Model;
use yii\validators\{NumberValidator, StringValidator};

/**
 * Model whose rules write options that the validator Yii creates can't assign; `string()` loses to the alias.
 */
final class ReportedModel extends Model
{
    /**
     * @return list<array{0: string, 1: \Closure|string, ...}>
     */
    public function rules(): array
    {
        return [
            ['title', 'datetime', 'formatt' => 'php:Y-m-d H:i'],
            ['title', 'integer', 'class' => StringValidator::class, 'integerOnly' => false],
            ['title', 'integer', 'minn' => 0],
            ['title', 'string', '__class' => NumberValidator::class, 'length' => 4],
            ['title', 'string', 'class' => NumberValidator::class, 'integerOnly' => false],
            ['title', 'string', 'maxx' => 10],
            ['title', 'time', 'formatt' => 'php:H:i'],
            ['title', static function (): void {}, 'maxx' => 10],
            ['title', StringValidator::class, 'maxx' => 1, 'minn' => 2],
            ['title', StringValidator::class, 'maxx' => 10],
            ['title', StringValidator::class, 99 => 'unexpected'],
        ];
    }

    public function string(): void {}
}
