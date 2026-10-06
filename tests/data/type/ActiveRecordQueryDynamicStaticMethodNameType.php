<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii2\extensions\phpstan\tests\support\stub\Invoice;

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for a static call with a dynamic method name on a model with a custom query class.
 *
 * Excluded from the project analysis because strict rules report the variable static method call.
 */
final class ActiveRecordQueryDynamicStaticMethodNameType
{
    public function testKeepMixedWhenStaticMethodNameIsDynamic(string $method): void
    {
        assertType(
            'mixed',
            Invoice::$method(),
        );
    }
}
