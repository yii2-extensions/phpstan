<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use yii\base\Component;

/**
 * Component whose `behaviors()` comes from a trait.
 */
final class TraitBehaviorsOwner extends Component
{
    use BehaviorsListTrait;
}
