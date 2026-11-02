<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use Closure;
use Yii;
use yii\base\{BaseObject, InvalidCallException, UnknownMethodException, UnknownPropertyException};
use yii\BaseYii;
use yii2\extensions\phpstan\tests\support\createobject\{
    AliasTarget,
    ChildTarget,
    ComponentTarget,
    CustomConstructorTarget,
    CustomSetterTarget,
    EmptyDefinitionTarget,
    Factory,
    FactoryTarget,
    InstanceTarget,
    SingletonTarget,
    Target,
    UnresolvedTarget,
};

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\CreateObjectUnknownOptionRuntimeTest} test cases.
 *
 * Provides the `Yii::createObject()` calls of the rule fixtures, one option at a time, with their runtime outcome.
 */
final class CreateObjectUnknownOptionRuntimeProvider
{
    /**
     * Calls whose option the rule reports, with the class and option named by the exception.
     *
     * @phpstan-return iterable<string, array{Closure(): object, string, string}>
     */
    public static function reportedOptionProvider(): iterable
    {
        yield 'unknown option' => [
            static fn(): object => Yii::createObject(['class' => Target::class, 'colour' => 'red']),
            Target::class,
            'colour',
        ];
        yield 'case-sensitive property name' => [
            static fn(): object => Yii::createObject(['class' => Target::class, 'Name' => 'value']),
            Target::class,
            'Name',
        ];
        yield 'protected property' => [
            static fn(): object => Yii::createObject(['class' => Target::class, 'internal' => 'value']),
            Target::class,
            'internal',
        ];
        yield 'private property' => [
            static fn(): object => Yii::createObject(['class' => Target::class, 'secret' => 'value']),
            Target::class,
            'secret',
        ];
        yield 'annotated property' => [
            static fn(): object => Yii::createObject(['class' => Target::class, 'documented' => 'value']),
            Target::class,
            'documented',
        ];
        yield 'annotated write-only property' => [
            static fn(): object => Yii::createObject(['class' => Target::class, 'annotated' => 'value']),
            Target::class,
            'annotated',
        ];
        yield 'parent private property' => [
            static fn(): object => Yii::createObject(['class' => ChildTarget::class, 'secret' => 'value']),
            ChildTarget::class,
            'secret',
        ];
        yield 'inherited protected property' => [
            static fn(): object => Yii::createObject(['class' => ChildTarget::class, 'internal' => 'value']),
            ChildTarget::class,
            'internal',
        ];
        yield '__class over class' => [
            static fn(): object => Yii::createObject(
                ['class' => ComponentTarget::class, '__class' => Target::class, 'colour' => 'red'],
            ),
            Target::class,
            'colour',
        ];
        yield 'event key on BaseObject' => [
            static fn(): object => Yii::createObject(['class' => BaseObject::class, 'on change' => 'event']),
            BaseObject::class,
            'on change',
        ];
        yield 'leading backslash' => [
            static fn(): object => Yii::createObject(['class' => '\\' . Target::class, 'colour' => 1]),
            Target::class,
            'colour',
        ];
        yield 'leading backslash misses the binding' => [
            static fn(): object => Yii::createObject(['class' => '\\' . FactoryTarget::class, 'colour' => 1]),
            FactoryTarget::class,
            'colour',
        ];
        yield 'BaseYii' => [
            static fn(): object => BaseYii::createObject(['class' => Target::class, 'colour' => 'base']),
            Target::class,
            'colour',
        ];
        yield 'BaseYii subclass' => [
            static fn(): object => Factory::createObject(['class' => Target::class, 'colour' => 'subclass']),
            Target::class,
            'colour',
        ];
        yield 'named arguments' => [
            static fn(): object => Yii::createObject(params: [], type: ['class' => Target::class, 'colour' => 'named']),
            Target::class,
            'colour',
        ];
    }

    /**
     * Calls the rule leaves alone, with the class of the created object or the exception they throw.
     *
     * @phpstan-return iterable<string, array{Closure(): object, class-string}>
     */
    public static function standAsideProvider(): iterable
    {
        yield 'factory ignores the configuration' => [
            static fn(): object => Yii::createObject(['class' => FactoryTarget::class, 'colour' => 'factory']),
            FactoryTarget::class,
        ];
        yield 'object definition ignores the configuration' => [
            static fn(): object => Yii::createObject(['class' => InstanceTarget::class, 'colour' => 'object']),
            InstanceTarget::class,
        ];
        yield 'untyped factory ignores the configuration' => [
            static fn(): object => Yii::createObject(['class' => UnresolvedTarget::class, 'colour' => 'unresolved']),
            UnresolvedTarget::class,
        ];
        yield 'existing singleton ignores the configuration' => [
            static function (): object {
                Yii::createObject(SingletonTarget::class);

                return Yii::createObject(['class' => SingletonTarget::class, 'colour' => 'singleton']);
            },
            SingletonTarget::class,
        ];
        yield 'first singleton call assigns the configuration' => [
            static fn(): object => Yii::createObject(['class' => SingletonTarget::class, 'colour' => 'singleton']),
            UnknownPropertyException::class,
        ];
        yield 'alias assigns to another class' => [
            static fn(): object => Yii::createObject(['class' => AliasTarget::class, 'label' => 'Target setter']),
            Target::class,
        ];
        yield 'empty definition assigns the configuration' => [
            static fn(): object => Yii::createObject(['class' => EmptyDefinitionTarget::class, 'colour' => 'empty']),
            UnknownPropertyException::class,
        ];
        yield 'custom constructor consumes the option' => [
            static fn(): object => Yii::createObject(
                ['class' => CustomConstructorTarget::class, 'alias' => 'consumed'],
            ),
            CustomConstructorTarget::class,
        ];
        yield 'custom __set() accepts the option' => [
            static fn(): object => Yii::createObject(['class' => CustomSetterTarget::class, 'colour' => 'magic']),
            CustomSetterTarget::class,
        ];
        yield 'component accepts an event handler' => [
            static fn(): object => Yii::createObject(
                ['class' => ComponentTarget::class, 'on change' => static function (): void {}],
            ),
            ComponentTarget::class,
        ];
        yield '__construct() moves the configuration to an ignored argument' => [
            static fn(): object => Yii::createObject(
                ['class' => Target::class, '__construct()' => [[], 'extra'], 'colour' => 'constructor'],
            ),
            Target::class,
        ];
        yield 'params move the configuration to an ignored argument' => [
            static fn(): object => Yii::createObject(['class' => Target::class, 'colour' => 'params'], [[], 'second']),
            Target::class,
        ];
        yield 'getter without setter' => [
            static fn(): object => Yii::createObject(['class' => Target::class, 'count' => 1]),
            InvalidCallException::class,
        ];
        yield 'private setter' => [
            static fn(): object => Yii::createObject(['class' => Target::class, 'hidden' => 'value']),
            UnknownMethodException::class,
        ];
        yield 'static property' => [
            static fn(): object => Yii::createObject(['class' => Target::class, 'shared' => 'value']),
            UnknownPropertyException::class,
        ];
        yield 'protected static property' => [
            static fn(): object => Yii::createObject(['class' => Target::class, 'registry' => 'value']),
            UnknownPropertyException::class,
        ];
    }

    /**
     * Calls whose options the created class assigns, with the class of the created object.
     *
     * @phpstan-return iterable<string, array{Closure(): object, class-string}>
     */
    public static function validOptionProvider(): iterable
    {
        yield 'public property, setter and case-insensitive setter' => [
            static fn(): object => Yii::createObject(
                ['class' => Target::class, 'name' => 'public', 'label' => 'setter', 'LABEL' => 'setter'],
            ),
            Target::class,
        ];
        yield 'protected setter and trait setter' => [
            static fn(): object => Yii::createObject(
                ['class' => Target::class, 'guarded' => 'protected setter', 'tagged' => 'trait setter'],
            ),
            Target::class,
        ];
        yield 'trait property' => [
            static fn(): object => Yii::createObject(['class' => Target::class, 'tag' => 'trait property']),
            Target::class,
        ];
        yield 'inherited property and setter' => [
            static fn(): object => Yii::createObject(
                ['class' => ChildTarget::class, 'name' => 'inherited', 'label' => 'inherited setter'],
            ),
            ChildTarget::class,
        ];
        yield 'own property and setter' => [
            static fn(): object => Yii::createObject(
                ['class' => ChildTarget::class, 'colourCode' => 'own property', 'shade' => 'own setter'],
            ),
            ChildTarget::class,
        ];
        yield '__class with ignored class' => [
            static fn(): object => Yii::createObject(
                ['__class' => Target::class, 'class' => ComponentTarget::class, 'name' => '__class wins'],
            ),
            Target::class,
        ];
    }
}
