<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveRecord;

/**
 * ActiveRecord fixture whose read-only getter is not a database attribute.
 *
 * @phpstan-property-read string $label
 */
final class VirtualAttributeRecord extends ActiveRecord
{
    /**
     * @return array{}
     */
    public function attributes(): array
    {
        return [];
    }

    public function getLabel(): string
    {
        return 'visible';
    }
}
