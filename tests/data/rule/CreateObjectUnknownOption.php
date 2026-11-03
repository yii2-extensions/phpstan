<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use Yii;
use Yii as ObjectFactory;
use yii\base\BaseObject;
use yii\BaseYii;
use yii2\extensions\phpstan\tests\support\createobject\{
    ChildTarget,
    ComponentTarget,
    Factory,
    Target,
};

/**
 * Literal `Yii::createObject()` options that the created class can't assign, one call per method.
 */
final class CreateObjectUnknownOption
{
    public function aliasedYii(): void
    {
        ObjectFactory::createObject(
            [
                'class' => Target::class,
                'colour' => 'alias',
            ],
        );
    }

    public function annotatedProperties(): void
    {
        Yii::createObject(
            [
                'class' => Target::class,
                'documented' => 'annotation',
                'annotated' => 'annotation',
            ],
        );
    }

    public function baseYii(): void
    {
        BaseYii::createObject(
            [
                'class' => Target::class,
                'colour' => 'base',
            ],
        );
    }

    public function caseInsensitiveMethodName(): void
    {
        Yii::createobject(
            [
                'class' => Target::class,
                'colour' => 'case-insensitive method',
            ],
        );
    }

    public function caseSensitivePropertyName(): void
    {
        Yii::createObject(
            [
                'class' => Target::class,
                'Name' => 'case-sensitive property',
            ],
        );
    }

    public function dunderClassOverClass(): void
    {
        Yii::createObject(
            [
                'class' => ComponentTarget::class,
                '__class' => Target::class,
                'colour' => 'red',
            ],
        );
    }

    public function duplicateOption(): void
    {
        Yii::createObject(
            [
                'class' => Target::class,
                'colour' => 'red',
                'colour' => 'blue',
            ],
        );
    }

    public function emptyParams(): void
    {
        Yii::createObject(
            [
                'class' => Target::class,
                'colour' => 'empty params',
            ],
            [],
        );
    }

    public function eventKeyOnBaseObject(): void
    {
        Yii::createObject(
            [
                'class' => BaseObject::class,
                'on change' => 'event',
            ],
        );
    }

    public function fullyQualifiedYii(): void
    {
        \Yii::createObject(
            [
                'class' => Target::class,
                'colour' => 'global',
            ],
        );
    }

    public function inheritedNonPublicProperties(): void
    {
        Yii::createObject(
            [
                'class' => ChildTarget::class,
                'secret' => 'parent private',
                'internal' => 'inherited',
            ],
        );
    }

    public function lastClassValue(): void
    {
        Yii::createObject(
            [
                'class' => ComponentTarget::class,
                'class' => Target::class,
                'colour' => 'last class',
            ],
        );
    }

    public function leadingBackslash(): void
    {
        Yii::createObject(
            [
                'class' => '\yii2\extensions\phpstan\tests\support\createobject\Target',
                'colour' => 1,
            ],
        );
    }

    public function leadingBackslashMissesBinding(): void
    {
        Yii::createObject(
            [
                'class' => '\yii2\extensions\phpstan\tests\support\createobject\FactoryTarget',
                'colour' => 1,
            ],
        );
    }

    public function namedArgument(): void
    {
        Yii::createObject(
            type: [
                'class' => Target::class,
                'colour' => 'named',
            ],
        );
    }

    public function namedArgumentsReordered(): void
    {
        Yii::createObject(
            params: [],
            type: [
                'class' => Target::class,
                'colour' => 'named reordered',
            ],
        );
    }

    public function nonPublicProperties(): void
    {
        Yii::createObject(
            [
                'class' => Target::class,
                'internal' => 'protected',
                'secret' => 'private',
            ],
        );
    }

    public function unknownAfterValidOption(): void
    {
        Yii::createObject(
            [
                'class' => Target::class,
                'name' => 'valid',
                'colour' => 'red',
            ],
        );
    }

    public function unknownOption(): void
    {
        Yii::createObject(
            [
                'class' => Target::class,
                'colour' => 'red',
            ],
        );
    }

    public function yiiSubclass(): void
    {
        Factory::createObject(
            [
                'class' => Target::class,
                'colour' => 'subclass',
            ],
        );
    }
}
