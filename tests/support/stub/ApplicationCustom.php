<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\web\{Application, IdentityInterface};

/**
 * Stub Web Application with a virtual property for PHPStan extension tests.
 *
 * @property string $virtualProperty
 *
 * @extends Application<IdentityInterface>
 */
final class ApplicationCustom extends Application {}
