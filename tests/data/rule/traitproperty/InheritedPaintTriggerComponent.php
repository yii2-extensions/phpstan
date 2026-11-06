<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Subclass of {@see PaintTriggerComponent} that inherits its trait-declared `$trigger` hook.
 */
final class InheritedPaintTriggerComponent extends PaintTriggerComponent {}
