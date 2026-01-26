<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\web\{Application, IdentityInterface};

/**
 * Custom application class with a virtual property for testing purposes.
 *
 * Extends the Yii Web Application to provide a virtual property for use in static analysis and testing scenarios.
 *
 * This class is intended for use in test suites to verify property reflection and PHPStan extension behavior.
 *
 * @property string $virtualProperty
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 *
 * @extends Application<IdentityInterface>
 */
final class ApplicationCustom extends Application {}
