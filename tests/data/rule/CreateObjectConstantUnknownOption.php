<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use Yii;
use yii2\extensions\phpstan\tests\support\createobject\{ComponentTarget, Options, Target};

/**
 * Constant-known `Yii::createObject()` options that the created class can't assign, one call per method.
 */
final class CreateObjectConstantUnknownOption
{
    private const string KEY = 'colour';

    public function assignedOffset(): void
    {
        $assigned = ['class' => Target::class, 'name' => 'public'];
        $assigned['colour'] = 'assigned offset';

        Yii::createObject($assigned);
    }

    public function classKeyConstant(): void
    {
        Yii::createObject(
            [
                Options::CLASS_KEY => Target::class,
                'colour' => 'constant class key',
            ],
        );
    }

    public function classValueConstant(): void
    {
        Yii::createObject(
            [
                'class' => Options::TARGET,
                'colour' => 'constant class value',
            ],
        );
    }

    public function constantConfiguration(): void
    {
        Yii::createObject(Options::CONFIG);
    }

    public function constantEmptyParams(): void
    {
        Yii::createObject(
            [
                'class' => Target::class,
                'colour' => 'constant empty params',
            ],
            Options::NO_PARAMS,
        );
    }

    public function copiedConstant(): void
    {
        $copy = Options::CONFIG;

        Yii::createObject($copy);
    }

    public function dunderClassConstant(): void
    {
        Yii::createObject(
            [
                'class' => ComponentTarget::class,
                '__class' => Options::TARGET,
                'colour' => 'constant __class value',
            ],
        );
    }

    public function leadingBackslashConstantMissesBinding(): void
    {
        Yii::createObject(
            [
                'class' => Options::FACTORY_TARGET_WITH_BACKSLASH,
                'colour' => 'leading backslash misses the binding',
            ],
        );
    }

    public function literalClassReplacedByUnpackedConstant(): void
    {
        Yii::createObject(
            [
                'class' => ComponentTarget::class,
                'colour' => 'replaced class',
                ...Options::TARGET_CLASS,
            ],
        );
    }

    public function literalKeyAfterConstantKey(): void
    {
        Yii::createObject(
            [
                'class' => Target::class,
                Options::UNKNOWN => 'first',
                'colour' => 'last',
            ],
        );
    }

    public function localClassValue(): void
    {
        $className = Target::class;

        Yii::createObject(
            [
                'class' => $className,
                'colour' => 'local class value',
            ],
        );
    }

    public function localConfiguration(): void
    {
        $config = ['class' => Target::class, 'colour' => 'local'];

        Yii::createObject($config);
    }

    public function localEmptyParams(): void
    {
        $params = [];

        Yii::createObject(
            ['class' => Target::class, 'colour' => 'local empty params'],
            $params,
        );
    }

    public function namedConstantConfigurationWithUnpackedParams(): void
    {
        Yii::createObject(
            params: [...Options::NO_PARAMS],
            type: Options::CONFIG,
        );
    }

    public function numericStringKey(): void
    {
        Yii::createObject(
            [
                'class' => Target::class,
                '1' => 'numeric string key stays a literal option name',
            ],
        );
    }

    public function otherClassConstantKey(): void
    {
        Yii::createObject(
            [
                'class' => Target::class,
                Options::UNKNOWN => 'other class constant key',
            ],
        );
    }

    public function selfConstantKey(): void
    {
        Yii::createObject(
            [
                'class' => Target::class,
                self::KEY => 'self constant key',
            ],
        );
    }

    public function twoUnpackedConstants(): void
    {
        Yii::createObject(
            [
                ...Options::CONFIG,
                ...Options::BASE,
            ],
        );
    }

    public function unionOfOneShape(bool $flag): void
    {
        $config = $flag ? Options::CONFIG : ['class' => Target::class, 'colour' => 'same keys, other value'];

        Yii::createObject($config);
    }

    public function unpackedComponentConfigurationWithReplacedClass(): void
    {
        Yii::createObject(
            [
                ...Options::COMPONENT_CONFIG,
                'class' => Target::class,
            ],
        );
    }

    public function unpackedConstantBeforeLiteral(): void
    {
        Yii::createObject(
            [
                ...Options::BASE,
                'name' => 'unpacked',
            ],
        );
    }
}
