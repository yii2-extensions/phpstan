<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\diagnostic;

use Yii;
use yii2\extensions\phpstan\tests\support\createobject\{Options, Target};

/**
 * Fixture for {@see \yii2\extensions\phpstan\tests\CreateObjectUnknownOptionDiagnosticTest} with native errors.
 */
final class CreateObjectUnknownOptionDiagnostic
{
    public function create(): void
    {
        Yii::createObject(['class' => Target::class, 'colour' => 'red']);
        Yii::createObject(['class' => Target::class, 'colour' => 'red', 'colour' => 'blue']);
        Yii::createObject(['class' => 'Missing\Target', 'colour' => 'red']);
        Yii::createObject(['class' => Target::class, 'colour' => 'red'], [], 'extra');
        Yii::createObject(type: ['class' => Target::class, 'colour' => 'red'], extra: true);
        Yii::createObject(['class' => Target::class, 'name' => 1, 'colour' => 'red']);
        Yii::createObject(['class' => Target::class, 'name' => $undefined]);
        Yii::createObject(['class' => Target::class, Options::UNKNOWN => 'red', 'colour' => 'blue']);

        $config = Options::CONFIG;

        Yii::createObject($config);
        Yii::createObject(['class' => Target::class, Options::UNKNOWN => 'red'], [], 'extra');
    }

    /**
     * @param array{class: Target::class, colour: string} $config
     */
    public function createDocumented(array $config): void
    {
        Yii::createObject($config);
    }
}
