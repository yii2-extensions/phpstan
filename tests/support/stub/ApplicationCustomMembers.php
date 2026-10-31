<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\web\{Application, IdentityInterface};

/**
 * Stub Web Application declaring its own native and annotated members for PHPStan extension tests.
 *
 * @property string $annotatedProperty
 *
 * @extends Application<IdentityInterface>
 */
final class ApplicationCustomMembers extends Application
{
    public int $nativeProperty = 0;
}
