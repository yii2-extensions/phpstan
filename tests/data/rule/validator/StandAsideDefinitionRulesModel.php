<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use Closure;
use stdClass;
use yii\base\Model;
use yii\validators\RequiredValidator;

/**
 * Model whose `rules()` returns validator types that the rule can't prove invalid; Yii rejects some of them at runtime.
 */
final class StandAsideDefinitionRulesModel extends Model
{
    /**
     * @var array<string, string>
     */
    public $documented = [];
    public bool $flag = false;
    public string|null $type = null;

    /**
     * @return array<array-key, mixed>
     */
    public function rules(): array
    {
        if ($this->scenario === 'merged') {
            return [
                ...parent::rules(),
                ['name', ['class' => RequiredValidator::class]],
            ];
        }

        if ($this->scenario === 'built') {
            $rules = [];

            foreach ($this->attributes() as $attribute) {
                $rules[] = [$attribute, ['class' => RequiredValidator::class]];
            }

            return $rules;
        }

        $optional = ['name'];

        if ($this->flag) {
            $optional[1] = ['class' => RequiredValidator::class];
        }

        return [
            ['name', $this->flag ? ['class' => RequiredValidator::class] : static fn(): bool => true],
            ['name', $this->flag ? ['class' => RequiredValidator::class] : 'required'],
            ['name', $this->object()],
            ['name', $this->documentedObject()],
            ['name', $this->closure()],
            ['name', $this->callable()],
            ['name', $this->documented],
            ['name', $this->mixed()],
            ['name', 5],
            ['name', 1.5],
            ['name', false],
            ['name', 'unknownValidatorName'],
            ['name', 'Unknown\\ValidatorClass'],
            ['name', stdClass::class],
            [null, ['class' => RequiredValidator::class]],
            [1 => ['class' => RequiredValidator::class]],
            [$this->type, ['class' => RequiredValidator::class]],
            $this->flag ? ['name', ['class' => RequiredValidator::class]] : ['name', 'required'],
            $optional,
            $this->documentedRule(),
            new RequiredValidator(),
        ];
    }

    private function callable(): callable
    {
        return 'trim';
    }

    private function closure(): Closure
    {
        return static fn(): bool => true;
    }

    /**
     * @return RequiredValidator
     */
    private function documentedObject(): object
    {
        return new RequiredValidator();
    }

    /**
     * @return array{0: 'name', 1: array{class: class-string<RequiredValidator>}}
     */
    private function documentedRule(): array
    {
        return ['name', ['class' => RequiredValidator::class]];
    }

    private function mixed(): mixed
    {
        return ['class' => RequiredValidator::class];
    }

    private function object(): object
    {
        return new RequiredValidator();
    }
}
