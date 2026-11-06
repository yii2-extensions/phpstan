<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Subclass of {@see WideCountObject} that inherits its trait-declared `$count` hook.
 */
final class InheritedWideCountObject extends WideCountObject {}
