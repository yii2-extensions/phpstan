<?php

declare(strict_types=1);

use yii\i18n\Formatter;
use yii2\extensions\phpstan\tests\support\stub\{ApplicationCustom, MyComponent};

return [
    'phpstan' => [
        'application_type' => ApplicationCustom::class,
    ],
    'components' => [
        'classlessComponent' => [
            'enabled' => true,
        ],
        'formatter' => static fn() => new Formatter(),
        'resolvedComponent' => [
            'class' => MyComponent::class,
        ],
        'unresolvedComponent' => static fn() => new MyComponent(),
    ],
];
