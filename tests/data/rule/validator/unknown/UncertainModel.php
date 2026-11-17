<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\unknown;

use stdClass;
use yii\base\Model;
use yii\validators\StringValidator;

/**
 * Model whose rules write unknown options where the validator type or its assignment path isn't certain.
 */
final class UncertainModel extends Model
{
    public function rules(): array
    {
        return [
            ['title', $this->callback(), 'maxx' => 10],
            ['title', $this->dynamic(), 'maxx' => 10],
            ['title', ConstructorValidator::class, 'maxx' => 10],
            ['title', MagicValidator::class, 'maxx' => 10],
            ['title', stdClass::class, 'maxx' => 10],
            ['title', StringValidator::class, 'as extra' => ['class' => 'dynamicBehavior'], 'maxx' => 10],
        ];
    }

    private function callback(): callable
    {
        return static function (): void {};
    }

    private function dynamic(): mixed
    {
        return 'string';
    }
}
