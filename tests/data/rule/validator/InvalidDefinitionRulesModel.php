<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use Stringable;
use yii\base\Model;
use yii\validators\{RequiredValidator, StringValidator};

/**
 * Model whose `rules()` returns validator types that `Validator::createValidator()` can't look up, beside valid rules.
 */
final class InvalidDefinitionRulesModel extends Model
{
    private const array CONFIGURED_TYPE = ['name', ['class' => RequiredValidator::class]];

    public bool $flag = false;
    public string $name = '';

    /**
     * @var array<array-key, mixed>
     */
    public array $options = [];

    public static function check(string $attribute): void {}

    /**
     * @return array<array-key, mixed>
     */
    public function rules(): array
    {
        return [
            ['name', 'required'],
            ['name', ['class' => RequiredValidator::class]],
            [['name', 'email'], ['class' => StringValidator::class, 'max' => 5], 'on' => 'default'],
            ['name', [self::class, 'check']],
            ['name', [$this, 'check']],
            ['name', []],
            ['name', new RequiredValidator()],
            ['name', new DefinitionInvocable()],
            ['name', $this],
            ['name', DefinitionKind::Required],
            ['name', $this->stringable()],
            ['name', $this->options],
            ['name', $this->flag ? ['class' => RequiredValidator::class] : new DefinitionInvocable()],
            ['name', 'trim', 1 => new DefinitionInvocable()],
            ['1' => ['class' => RequiredValidator::class], '0' => 'name'],
            'named' => ['name', [RequiredValidator::class]],
            self::CONFIGURED_TYPE,
            ['name', ['class' => RequiredValidator::class]],
            ['name', static fn(string $attribute): bool => true],
            ['name', 'string', 'max' => 5],
        ];
    }

    private function stringable(): Stringable
    {
        return new class implements Stringable {
            public function __toString(): string
            {
                return 'required';
            }
        };
    }
}
