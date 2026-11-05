<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\widget;

use yii\base\InvalidConfigException;

/**
 * Subclass of {@see TargetWidget} whose `init()` rejects an empty `name` after Yii assigns the configuration.
 */
class InitFailingWidget extends TargetWidget
{
    public function init(): void
    {
        parent::init();

        if ($this->name === '') {
            throw new InvalidConfigException('The "name" option is required.');
        }
    }
}
