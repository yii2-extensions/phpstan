<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

/**
 * Interface without a container binding, which the container can't instantiate.
 */
interface UnboundContract {}
