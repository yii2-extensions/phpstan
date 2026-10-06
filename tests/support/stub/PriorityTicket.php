<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

/**
 * Stub subclass of {@see Ticket} inheriting its bound `find()` for query model inference tests.
 *
 * @property int $priority
 */
final class PriorityTicket extends Ticket {}
