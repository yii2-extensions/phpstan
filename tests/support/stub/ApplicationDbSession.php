<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\web\{Application, DbSession, IdentityInterface};

/**
 * Stub Web Application narrowing its inherited session annotation for PHPStan extension tests.
 *
 * @property DbSession $session
 *
 * @extends Application<IdentityInterface>
 */
final class ApplicationDbSession extends Application {}
