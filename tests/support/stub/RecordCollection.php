<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use ArrayIterator;
use IteratorAggregate;

/**
 * Stub generic collection used as the type of a to-many relation `@property` tag on {@see Ledger}.
 *
 * @template T of object
 *
 * @implements IteratorAggregate<int, T>
 */
final class RecordCollection implements IteratorAggregate
{
    /**
     * @param list<T> $items Items of the collection.
     */
    public function __construct(private readonly array $items = []) {}

    /**
     * @return ArrayIterator<int, T>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }
}
