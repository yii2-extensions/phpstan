<?php

declare(strict_types=1);

// the class is outside PSR-4 autoloading, so the configuration loads it, as an application without autoloading would
require_once dirname(__DIR__) . '/support/stub/GlobalApplication.php';

return [
    'phpstan' => [
        'application_type' => 'GlobalApplication',
    ],
];
