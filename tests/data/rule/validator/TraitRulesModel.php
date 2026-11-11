<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use yii\base\Model;

/**
 * Model whose `rules()` comes from a trait.
 */
final class TraitRulesModel extends Model
{
    use RulesListTrait;
}
