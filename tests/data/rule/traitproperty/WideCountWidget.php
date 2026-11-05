<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Subclass of {@see CountWidget} whose `$count` set hook comes from {@see WideCountTrait}.
 */
final class WideCountWidget extends CountWidget
{
    use WideCountTrait;
}
