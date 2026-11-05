<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\action;

/**
 * Action with a constructor that forwards its arguments unchanged.
 */
final class ForwardingConstructorAction extends TargetAction
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(mixed $id, mixed $controller, array $config = [])
    {
        parent::__construct($id, $controller, $config);
    }
}
