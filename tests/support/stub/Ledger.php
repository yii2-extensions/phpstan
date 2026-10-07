<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use DateTimeImmutable;
use yii\db\ActiveRecord;

/**
 * Stub ActiveRecord model with borderline `@property` tag types for `asArray()` row shape classification tests.
 *
 * @property int $id
 * @property string[] $tags
 * @property array<mixed>|Comment $attachment
 * @property mixed $payload
 * @property array<mixed> $options
 * @property object $meta
 * @property array<Comment|string> $entries
 * @property array<Comment|null> $drafts
 * @property RecordCollection<Comment> $thread
 * @property DateTimeImmutable $created_at
 * @property LedgerStatus $status
 * @property MyComponent $owner
 * @property DateTimeImmutable[] $history
 */
final class Ledger extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'ledgers';
    }
}
