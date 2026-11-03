<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

/**
 * Non-final class without `__toString()`, which a `Stringable` subclass can extend.
 */
class TypedPlain {}
