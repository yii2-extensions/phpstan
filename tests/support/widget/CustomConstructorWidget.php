<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\widget;

/**
 * Subclass of {@see TargetWidget} with a constructor that removes the `colour` option before forwarding the rest.
 */
class CustomConstructorWidget extends TargetWidget
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        unset($config['colour']);

        parent::__construct($config);
    }
}
