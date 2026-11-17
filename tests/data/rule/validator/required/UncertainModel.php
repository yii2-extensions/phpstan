<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\required;

use yii\base\Model;
use yii\validators\RangeValidator;

/**
 * Model whose rules leave the required option or the assignment path unproven, or fall outside the checked classes.
 */
final class UncertainModel extends Model
{
    /**
     * @return list<array<array-key, mixed>>
     */
    public function rules(): array
    {
        return [
            ['status', 'in', 'range' => $this->optionalRange()],
            ['status', $this->dynamicType()],
            ['code', 'match', 'as pattern' => PatternBehavior::class],
            ['status', 'in', 'rnage' => [1]],
            ['status', 'in', 'validationAttributes' => []],
            ['status', 'in', 'range' => 'a,b'],
            ['code', InitPatternValidator::class],
            ['tags', 'each', 'rule' => ['in']],
            ['status', 'app\validators\NullRange'],
            ['status', 'string', '__class' => $this->dynamicClass()],
        ];
    }

    private function dynamicClass(): string
    {
        return RangeValidator::class;
    }

    private function dynamicType(): mixed
    {
        return 'in';
    }

    /**
     * @return list<int>|null
     */
    private function optionalRange(): array|null
    {
        return null;
    }
}
