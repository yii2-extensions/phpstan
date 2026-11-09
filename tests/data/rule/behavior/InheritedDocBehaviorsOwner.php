<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use yii\base\Component;

/**
 * Component whose `behaviors()` inherits Yii's declared return type, which already rejects arrays without a class.
 */
final class InheritedDocBehaviorsOwner extends Component
{
    public function behaviors(): array
    {
        return ['noClass' => ['label' => 'value']];
    }
}
