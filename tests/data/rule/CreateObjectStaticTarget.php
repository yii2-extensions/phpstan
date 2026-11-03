<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use Yii;
use yii\base\BaseObject;

/**
 * Final configurable class that creates itself through `static::class`, which its native type proves to be one class.
 */
final class CreateObjectStaticTarget extends BaseObject
{
    private const string KEY = 'colour';

    public static function createWithConstantKey(): object
    {
        return Yii::createObject(['class' => static::class, self::KEY => 'late static class, constant key']);
    }

    public static function createWithLiteralKeys(): object
    {
        return Yii::createObject(['class' => static::class, 'colour' => 'late static class, literal keys']);
    }
}
