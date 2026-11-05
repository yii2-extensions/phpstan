<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

use yii\base\Component;

/**
 * Component whose protected `$trigger` comes from {@see ProtectedTriggerTrait}.
 */
class ProtectedTriggerComponent extends Component
{
    use ProtectedTriggerTrait;
}
