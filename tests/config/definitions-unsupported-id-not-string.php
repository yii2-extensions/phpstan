<?php

declare(strict_types=1);

use yii2\extensions\phpstan\tests\support\stub\MyActiveRecord;

return [
    'container' => [
        'definitions' => [
            1 => MyActiveRecord::class,
        ],
    ],
];
