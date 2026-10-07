<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

/**
 * Stub non-module class declaring its own `params` property, for application params inference tests.
 */
final class ApplicationParamsOwner
{
    /**
     * @var array{maxItems: string}
     */
    public array $params = ['maxItems' => ''];
}
