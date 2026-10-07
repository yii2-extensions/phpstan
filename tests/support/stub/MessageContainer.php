<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

/**
 * Stub class with a `messages` property unrelated to the Yii logger, for logger message inference tests.
 */
final class MessageContainer
{
    /**
     * @var array<mixed>
     */
    public array $messages = [];
}
