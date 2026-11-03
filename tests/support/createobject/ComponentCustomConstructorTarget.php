<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use yii\base\Component;

/**
 * Component target whose constructor consumes the `alias` option before forwarding the remaining configuration.
 */
final class ComponentCustomConstructorTarget extends Component
{
    public int $count = 0;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        unset($config['alias']);

        parent::__construct($config);
    }
}
