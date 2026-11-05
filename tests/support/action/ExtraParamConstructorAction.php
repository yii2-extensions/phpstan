<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\action;

/**
 * Action with a constructor parameter between the controller and the configuration.
 */
final class ExtraParamConstructorAction extends TargetAction
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(mixed $id, mixed $controller, public string $mode = 'plain', array $config = [])
    {
        parent::__construct($id, $controller, $config);
    }
}
