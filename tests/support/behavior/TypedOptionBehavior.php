<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

use yii\base\Behavior;

/**
 * Behavior with natively typed and PHPDoc-only typed members that a configuration option may reach.
 */
class TypedOptionBehavior extends Behavior
{
    public int $count = 0;
    /**
     * @var int
     */
    public $documented = 0;
    public int|null $limit = null;
    public string $title = '';
    protected int $guarded = 0;

    public function setGuarded(string $value): void {}

    /**
     * @param array<mixed> $value
     */
    public function setTags(array $value): void {}
}
