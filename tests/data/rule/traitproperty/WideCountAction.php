<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Subclass of {@see CountAction} whose `$count` set hook comes from {@see WideCountTrait}.
 */
final class WideCountAction extends CountAction
{
    use WideCountTrait;
}
