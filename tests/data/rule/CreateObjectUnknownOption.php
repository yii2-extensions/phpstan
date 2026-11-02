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
 * Literal `Yii::createObject()` options that the created class can't assign, one call per line.
 */
final class CreateObjectUnknownOption
{
    public function reportThroughEveryCallForm(): void
    {
        \Yii::createObject(
            [
                'class' => Target::class,
                'colour' => 'global',
            ],
        );
        ObjectFactory::createObject(
            [
                'class' => Target::class,
                'colour' => 'alias',
            ],
        );
        BaseYii::createObject(
            [
                'class' => Target::class,
                'colour' => 'base',
            ],
        );
        Factory::createObject(
            [
                'class' => Target::class,
                'colour' => 'subclass',
            ],
        );
        Yii::createobject(
            [
                'class' => Target::class,
                'colour' => 'case-insensitive method',
            ],
        );
        Yii::createObject(
            type: [
                'class' => Target::class,
                'colour' => 'named',
            ],
        );
        Yii::createObject(
            params: [],
            type: [
                'class' => Target::class,
                'colour' => 'named reordered',
            ],
        );
        Yii::createObject(
            [
                'class' => Target::class,
                'colour' => 'empty params',
            ],
            [],
        );
    }

    public function reportUnknownOptions(): void
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
                'Name' => 'case-sensitive property',
            ],
        );
        Yii::createObject(
            [
                'class' => Target::class,
                'internal' => 'protected',
                'secret' => 'private',
            ],
        );
        Yii::createObject(
            [
                'class' => Target::class,
                'documented' => 'annotation',
                'annotated' => 'annotation',
            ],
        );
        Yii::createObject(
            [
                'class' => ChildTarget::class,
                'secret' => 'parent private',
                'internal' => 'inherited',
            ],
        );
        Yii::createObject(
            [
                'class' => ComponentTarget::class,
                '__class' => Target::class,
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
                'class' => BaseObject::class,
                'on change' => 'event',
            ],
        );
        Yii::createObject(
            [
                'class' => '\yii2\extensions\phpstan\tests\support\createobject\Target',
                'colour' => 1,
            ],
        );
        Yii::createObject(
            [
                'class' => '\yii2\extensions\phpstan\tests\support\createobject\FactoryTarget',
                'colour' => 1,
            ],
        );
        Yii::createObject(
            [
                'class' => ComponentTarget::class,
                'class' => Target::class,
                'colour' => 'last class',
            ],
        );
        Yii::createObject(
            [
                'class' => Target::class,
                'name' => 'valid',
                'colour' => 'red',
            ],
        );
    }
}
