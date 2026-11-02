<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use yii\base\BaseObject;

/**
 * Target whose constructor consumes the `alias` option before forwarding the remaining configuration.
 */
final class CustomConstructorTarget extends BaseObject
{
    public string $name = '';

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        unset($config['alias']);

        parent::__construct($config);
    }
}
