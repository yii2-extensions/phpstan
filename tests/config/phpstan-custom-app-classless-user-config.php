<?php

declare(strict_types=1);

use yii2\extensions\phpstan\tests\support\stub\{ApplicationCustom, User};

return [
    'phpstan' => [
        'application_type' => ApplicationCustom::class,
    ],
    'components' => [
        'user' => [
            'identityClass' => User::class,
        ],
    ],
];
