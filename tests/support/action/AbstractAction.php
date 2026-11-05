<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\action;

use yii\base\Action;

/**
 * Abstract action that the container can't instantiate.
 */
abstract class AbstractAction extends Action
{
    public string $name = '';
}
