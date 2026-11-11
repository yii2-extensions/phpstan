<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use yii\base\Model;
use yii\validators\RequiredValidator;

/**
 * Model whose `rules()` returns rules that the rule can't prove incomplete; Yii rejects some of them at runtime.
 */
final class StandAsideRulesModel extends Model
{
    public bool $flag = false;
    public string|null $type = null;

    /**
     * @return array<array-key, mixed>
     */
    public function rules(): array
    {
        $optional = ['name'];

        if ($this->flag) {
            $optional[1] = 'required';
        }

        return [
            [
                'name',
                $this->type,
            ],
            // the possibly `null` key 0 makes the rule stand aside, although key 1 is absent
            [$this->type],
            [$this->flag ? null : 'name', 'required'],
            $this->flag ? ['name'] : ['name', 'required'],
            $optional,
            $this->documented(),
            'name',
            null,
            new RequiredValidator(),
        ];
    }

    /**
     * @return array{0: 'name'}
     */
    private function documented(): array
    {
        return ['name'];
    }
}
