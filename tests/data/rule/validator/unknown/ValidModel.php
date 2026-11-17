<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\unknown;

use yii\base\Model;
use yii\validators\{DateValidator, StringValidator};

/**
 * Model whose rules write only options that the validator Yii creates accepts.
 */
final class ValidModel extends Model
{
    /**
     * @return list<array{0: string, 1: \Closure|string, ...}>
     */
    public function rules(): array
    {
        return [
            ['title', 'string', 'max' => 10],
            ['title', 'integer', 'min' => 0],
            ['title', 'time', 'format' => 'php:H:i', 'locale' => 'en-US', 'timeZone' => 'UTC'],
            ['title', 'datetime', 'format' => 'php:Y-m-d H:i', 'locale' => 'en-US', 'timeZone' => 'UTC'],
            ['title', 'string', 'current' => 'discarded'],
            ['title', static function (): void {}, 'skipOnEmpty' => false],
            ['title', StringValidator::class, 'max' => 10],
            [
                'title',
                'time',
                'class' => DateValidator::class,
                'type' => DateValidator::TYPE_DATETIME,
                'format' => 'php:Y-m-d H:i',
                'locale' => 'en-US',
                'timeZone' => 'UTC',
            ],
        ];
    }
}
