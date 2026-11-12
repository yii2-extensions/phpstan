<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use Closure;
use yii\base\Model;
use yii\validators\RequiredValidator;

/**
 * Model whose `rules()` returns `Closure`, name and class validator types and a validator instance, which Yii creates.
 */
final class ValidDefinitionRulesModel extends Model
{
    public string $name = '';

    /**
     * @return array<array-key, mixed>
     */
    public function rules(): array
    {
        return [
            ['name', function (string $attribute): void {}],
            ['name', static fn(string $attribute): bool => true],
            ['name', $this->validateName(...)],
            ['name', Closure::fromCallable([$this, 'validateName'])],
            ['name', 'required'],
            ['name', RequiredValidator::class],
            ['name', 'validateName'],
            ['name', 'string', 'max' => 5],
            new RequiredValidator(['attributes' => ['name']]),
        ];
    }

    public function validateName(string $attribute): void {}
}
