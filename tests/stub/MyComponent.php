<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\stub;

use yii\base\Component;

/**
 * Component for testing annotation-based virtual property resolution and native method exposure.
 *
 * Provides a minimal component implementation with a virtual property defined via PHPDoc and a single native method.
 *
 * This class is used in static analysis and behavior extension tests to validate property and method resolution in Yii
 * Component.
 *
 * @property string $virtualProperty
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class MyComponent extends Component
{
    public function nativeMethod(): string
    {
        return 'native';
    }
}
