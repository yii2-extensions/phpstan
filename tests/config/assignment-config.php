<?php

declare(strict_types=1);

// named by string: PHPStan parses the analysed sources as PHP 8.3, which has no asymmetric visibility; the `class` key
// keeps `ServiceMap` from autoloading the PHP 8.4 fixture
$target = 'yii2\extensions\phpstan\tests\data\rule\assignment\VisibilityHookTarget';

return [
    'container' => [
        'definitions' => [
            'assignment.hooked' => ['class' => $target, 'hooked' => 1],
            'assignment.plain' => ['class' => $target, 'plain' => 1],
            'assignment.privateSet' => ['class' => $target, 'privateSet' => 1],
            'assignment.protectedSet' => ['class' => $target, 'protectedSet' => 1],
        ],
    ],
];
