<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use yii\BaseYii;
use yii\web\IdentityInterface;

/**
 * Subclass of {@see BaseYii} that inherits `createObject()`.
 *
 * @extends BaseYii<IdentityInterface>
 */
final class Factory extends BaseYii {}
