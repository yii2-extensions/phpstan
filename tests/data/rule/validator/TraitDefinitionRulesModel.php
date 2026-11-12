<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use yii\base\Model;

/**
 * Model whose `rules()`, returning an invalid validator type, comes from a trait.
 */
final class TraitDefinitionRulesModel extends Model
{
    use DefinitionRulesTrait;
}
