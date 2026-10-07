<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\web\View;

/**
 * Stub factory with an `__invoke()` method that creates a {@see View}, used as a component and as a container
 * definition.
 */
final class InvokableViewFactory
{
    public function __invoke(): View
    {
        return new View();
    }
}
