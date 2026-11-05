<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

use yii\base\Component;

/**
 * Component with a public `$trigger` property.
 */
class TriggerComponent extends Component
{
    public mixed $trigger = null;
}
