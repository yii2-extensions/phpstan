<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\widget;

/**
 * Subclass of {@see TargetWidget} that the analysis container binds to {@see PlainObject}, which isn't a widget.
 */
class ObjectBoundWidget extends TargetWidget {}
