<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action\hook;

/**
 * Subclass of {@see TraitHookedIdAction} that inherits its trait-declared `id` hook.
 */
final class TraitHookedChildAction extends TraitHookedIdAction {}
