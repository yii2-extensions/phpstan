<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\web\Application;

/**
 * Stub Web Application declaring its own native and annotated members and a concrete identity for PHPStan extension
 * tests.
 *
 * @property string $annotatedProperty
 *
 * @extends Application<User>
 */
final class ApplicationCustomMembers extends Application
{
    public int $nativeProperty = 0;
}
