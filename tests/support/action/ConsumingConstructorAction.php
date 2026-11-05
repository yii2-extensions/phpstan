<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\action;

/**
 * Action with a constructor that removes the `colour` option before forwarding the configuration.
 */
final class ConsumingConstructorAction extends TargetAction
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(mixed $id, mixed $controller, array $config = [])
    {
        unset($config['colour']);

        parent::__construct($id, $controller, $config);
    }
}
