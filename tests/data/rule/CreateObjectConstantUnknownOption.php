<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use Yii;
use yii2\extensions\phpstan\tests\support\createobject\{ComponentTarget, Options, Target};

/**
 * Constant-known `Yii::createObject()` options that the created class can't assign.
 */
final class CreateObjectConstantUnknownOption
{
    private const string KEY = 'colour';

    public function reportAtCallLine(): void
    {
        $config = ['class' => Target::class, 'colour' => 'local'];

        Yii::createObject($config);
        Yii::createObject(Options::CONFIG);

        $copy = Options::CONFIG;

        Yii::createObject($copy);

        $assigned = ['class' => Target::class, 'name' => 'public'];
        $assigned['colour'] = 'assigned offset';

        Yii::createObject($assigned);
        Yii::createObject(
            [
                ...Options::BASE,
                'name' => 'unpacked',
            ],
        );
        Yii::createObject(
            [
                ...Options::COMPONENT_CONFIG,
                'class' => Target::class,
            ],
        );
        Yii::createObject(
            [
                'class' => ComponentTarget::class,
                'colour' => 'replaced class',
                ...Options::TARGET_CLASS,
            ],
        );
        Yii::createObject(
            [
                ...Options::CONFIG,
                ...Options::BASE,
            ],
        );
    }

    public function reportAtKeyLine(): void
    {
        Yii::createObject(
            [
                'class' => Target::class,
                Options::UNKNOWN => 'other class constant key',
            ],
        );
        Yii::createObject(
            [
                'class' => Target::class,
                self::KEY => 'self constant key',
            ],
        );
        Yii::createObject(
            [
                Options::CLASS_KEY => Target::class,
                'colour' => 'constant class key',
            ],
        );
        Yii::createObject(
            [
                'class' => Options::TARGET,
                'colour' => 'constant class value',
            ],
        );

        $className = Target::class;

        Yii::createObject(
            [
                'class' => $className,
                'colour' => 'local class value',
            ],
        );
        Yii::createObject(
            [
                'class' => ComponentTarget::class,
                '__class' => Options::TARGET,
                'colour' => 'constant __class value',
            ],
        );
        Yii::createObject(
            [
                'class' => Target::class,
                Options::UNKNOWN => 'first',
                'colour' => 'last',
            ],
        );
        Yii::createObject(
            [
                'class' => Options::FACTORY_TARGET_WITH_BACKSLASH,
                'colour' => 'leading backslash misses the binding',
            ],
        );
    }

    public function reportWithKnownEmptyParams(): void
    {
        $params = [];

        Yii::createObject(
            [
                'class' => Target::class,
                'colour' => 'constant empty params',
            ],
            Options::NO_PARAMS,
        );
        Yii::createObject(
            ['class' => Target::class, 'colour' => 'local empty params'],
            $params,
        );
        Yii::createObject(
            params: [...Options::NO_PARAMS],
            type: Options::CONFIG,
        );
    }

    public function reportWithoutKeyConversionInLiteral(): void
    {
        Yii::createObject(
            [
                'class' => Target::class,
                '1' => 'numeric string key stays a literal option name',
            ],
        );
    }

    public function reportWithUnionOfOneShape(bool $flag): void
    {
        $config = $flag ? Options::CONFIG : ['class' => Target::class, 'colour' => 'same keys, other value'];

        Yii::createObject($config);
    }
}
