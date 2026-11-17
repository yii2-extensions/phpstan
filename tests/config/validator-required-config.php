<?php

declare(strict_types=1);

use yii\validators\RangeValidator;
use yii2\extensions\phpstan\tests\data\rule\validator\required\SubRangeValidator;

return [
    'container' => [
        'definitions' => [
            'app\validators\ConfiguredRange' => ['class' => RangeValidator::class, 'range' => [1, 2]],
            'app\validators\FactoryRange' => static fn(): RangeValidator => new RangeValidator(['range' => [1]]),
            'app\validators\MappedRange' => SubRangeValidator::class,
            'app\validators\MessageRange' => ['class' => RangeValidator::class, 'message' => 'Invalid.'],
            'app\validators\NullRange' => ['class' => RangeValidator::class, 'range' => null],
        ],
    ],
];
