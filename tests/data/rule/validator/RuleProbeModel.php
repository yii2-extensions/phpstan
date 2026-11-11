<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use yii\base\Model;

/**
 * Model whose `rules()` returns the list assigned to it, so that runtime tests can create the validators of one rule.
 */
final class RuleProbeModel extends Model
{
    /**
     * @param array<array-key, mixed> $ruleList Rules to return.
     */
    public function __construct(private readonly array $ruleList)
    {
        parent::__construct();
    }

    /**
     * @return array<array-key, mixed>
     */
    public function rules(): array
    {
        return $this->ruleList;
    }
}
