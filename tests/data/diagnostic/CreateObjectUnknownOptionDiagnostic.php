<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\diagnostic;

use Yii;
use yii2\extensions\phpstan\tests\support\createobject\{Options, Target, TypedTarget};

/**
 * Fixture for {@see \yii2\extensions\phpstan\tests\CreateObjectUnknownOptionDiagnosticTest} with native errors.
 */
final class CreateObjectUnknownOptionDiagnostic
{
    public function create(): void
    {
        Yii::createObject(
            [
                'class' => Target::class,
                'colour' => 'red',
            ],
        );
        Yii::createObject(
            [
                'class' => Target::class,
                'colour' => 'red',
                'colour' => 'blue',
            ],
        );
        Yii::createObject(
            [
                'class' => 'Missing\Target',
                'colour' => 'red',
            ],
        );
        Yii::createObject(
            [
                'class' => Target::class,
                'colour' => 'red',
            ],
            [],
            'extra',
        );
        Yii::createObject(
            type: [
                'class' => Target::class,
                'colour' => 'red',
            ],
            extra: true,
        );
        Yii::createObject(
            [
                'class' => Target::class,
                'name' => 1,
                'colour' => 'red',
            ],
        );
        Yii::createObject(
            [
                'class' => Target::class,
                'name' => $undefined,
            ],
        );
        Yii::createObject(
            [
                'class' => Target::class,
                Options::UNKNOWN => 'red',
                'colour' => 'blue',
            ],
        );

        $config = Options::CONFIG;

        Yii::createObject(
            $config,
        );
        Yii::createObject(
            [
                'class' => Target::class,
                Options::UNKNOWN => 'red',
            ],
            [],
            'extra',
        );
    }

    /**
     * @param array{class: Target::class, colour: string} $config
     */
    public function createDocumented(array $config): void
    {
        Yii::createObject($config);
    }

    public function createTyped(): void
    {
        Yii::createObject(
            [
                'class' => TypedTarget::class,
                'count' => 'abc',
            ],
        );
        Yii::createObject(
            [
                'class' => TypedTarget::class,
                'count' => [],
                'colour' => 'red',
            ]
        );
        Yii::createObject(
            [
                'class' => TypedTarget::class,
                'count' => 'abc',
                'count' => 5,
            ]
        );
        Yii::createObject(
            [
                'class' => TypedTarget::class,
                'count' => 'abc',
            ],
            [],
            'extra'
        );
        Yii::createObject(
            [
                'class' => TypedTarget::class,
                'count' => '5',
                'title' => 5,
            ]
        );
    }

    public function createWithComponents(): void
    {
        Yii::createObject(
            [
                'class' => Options::COMPONENT,
                'colour' => 'red',
            ]
        );
        Yii::createObject(
            [
                'class' => Options::COMPONENT,
                'count' => 'abc',
            ]
        );
        Yii::createObject(
            [
                'class' => Options::COMPONENT,
                'count' => 'abc',
                'colour' => 'red',
            ]
        );
        Yii::createObject(
            [
                'class' => Options::COMPONENT,
                'as colour' => Options::COMPONENT_BEHAVIOR,
                'colour' => 'red',
            ]
        );
        Yii::createObject(
            [
                'class' => Options::COMPONENT_BEHAVIORS,
                'colour' => 'red',
                'count' => 'abc',
            ]
        );
        Yii::createObject(
            [
                'class' => Options::COMPONENT,
                'colour' => 'red',
                'colour' => 'blue',
            ]
        );
    }
}
