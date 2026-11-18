<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\type;

use yii\base\Model;
use yii\di\Instance;

/**
 * Model whose rules leave the value type, the created class or the assignment path unproven.
 */
final class UncertainModel extends Model
{
    public function checkCount(): void {}

    /**
     * @return list<array<array-key, mixed>>
     */
    public function rules(): array
    {
        return [
            ['count', TypedValidator::class, 'limit' => $this->mixedLimit()],
            ['count', TypedValidator::class, 'frozen' => 'all'],
            ['count', TypedValidator::class, 'label' => Instance::of('app.label')],
            ['count', ConstructorValidator::class, 'limit' => 'all'],
            ['count', 'checkCount', 'limit' => 'all'],
            ['count', 'boolean', 'class' => TypedValidator::class, 'limit' => 'all'],
            ['count', 'app\validators\FactoryLimited', 'limit' => 'all'],
            ['count', 'string', '__class' => $this->dynamicClass(), 'limit' => 'all'],
            ['count', $this->dynamicType(), 'limit' => 'all'],
            ['count', 'unknownAlias', 'limit' => 'all'],
        ];
    }

    private function dynamicClass(): string
    {
        return TypedValidator::class;
    }

    private function dynamicType(): mixed
    {
        return TypedValidator::class;
    }

    private function mixedLimit(): int|string
    {
        return 'all';
    }
}
