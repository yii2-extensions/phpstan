<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\base\Component;

/**
 * Stub component with a virtual property and a native method for property and method resolution tests.
 *
 * @property string $virtualProperty
 */
final class MyComponent extends Component
{
    public function nativeMethod(): string
    {
        return 'native';
    }
}
