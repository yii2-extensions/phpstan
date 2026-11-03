<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use Yii;
use yii2\extensions\phpstan\tests\support\createobject\{
    AliasTarget,
    CustomConstructorTarget,
    CustomSetterTarget,
    EmptyDefinitionTarget,
    FactoryTarget,
    InstanceTarget,
    SingletonTarget,
    TypedPlain,
    TypedStringablePlain,
    TypedSuit,
    TypedTarget,
    UnresolvedTarget,
};

use const INF;

/**
 * `Yii::createObject()` option values whose rejection the rule doesn't claim, one per method.
 */
final class CreateObjectOptionTypeStandAside
{
    public function aliasBinding(): object
    {
        return Yii::createObject(['class' => AliasTarget::class, 'name' => []]);
    }

    public function arrayForCallableSetter(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'handler' => []]);
    }

    public function constructorArguments(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, '__construct()' => [[], 'x'], 'count' => 'abc']);
    }

    public function customConstructor(): object
    {
        return Yii::createObject(['class' => CustomConstructorTarget::class, 'name' => []]);
    }

    public function customMagicSetter(): object
    {
        return Yii::createObject(['class' => CustomSetterTarget::class, 'extra' => 'abc']);
    }

    public function declaredObjectForStringSetter(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'text' => self::plain()]);
    }

    public function dynamicKey(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, self::text() => []]);
    }

    public function emptyDefinitionBinding(): object
    {
        return Yii::createObject(['class' => EmptyDefinitionTarget::class, 'name' => []]);
    }

    public function emptyOptionName(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, '' => 'abc']);
    }

    public function enumCaseForStringSetter(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'text' => TypedSuit::Hearts]);
    }

    public function factoryBinding(): object
    {
        return Yii::createObject(['class' => FactoryTarget::class, 'name' => []]);
    }

    public function infinityForIntProperty(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'count' => INF]);
    }

    public function instanceBinding(): object
    {
        return Yii::createObject(['class' => InstanceTarget::class, 'name' => []]);
    }

    public function intForFalseSetter(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'disabled' => 0]);
    }

    public function nonEmptyParams(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'count' => 'abc'], [[], 'second']);
    }

    public function nonFinalObjectForStringSetter(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'text' => new TypedPlain()]);
    }

    public function partiallyCompatibleUnion(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'count' => self::text() === '' ? 5 : 'abc']);
    }

    public function phpDocOnlyConfiguration(): object
    {
        return Yii::createObject(self::configuration());
    }

    public function phpDocOnlyValue(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'count' => self::documentedArray()]);
    }

    public function privateSetter(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'secret' => 'abc']);
    }

    public function readonlyProperty(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'frozen' => 'abc']);
    }

    public function singletonBinding(): object
    {
        return Yii::createObject(['class' => SingletonTarget::class, 'name' => []]);
    }

    public function stringValueOfUnknownContent(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'count' => self::text()]);
    }

    public function unknownFunctionForCallableSetter(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'handler' => 'yii2_phpstan_missing_function']);
    }

    public function unknownOption(): object
    {
        return Yii::createObject(['class' => TypedTarget::class, 'missing' => []]);
    }

    public function unresolvedBinding(): object
    {
        return Yii::createObject(['class' => UnresolvedTarget::class, 'name' => []]);
    }

    /**
     * @return array{class: class-string<TypedTarget>, count: 'abc'}
     */
    private static function configuration(): mixed
    {
        return ['class' => TypedTarget::class, 'count' => 'abc'];
    }

    /**
     * @return array<string>
     */
    private static function documentedArray(): mixed
    {
        return [];
    }

    private static function plain(): TypedPlain
    {
        return new TypedStringablePlain();
    }

    private static function text(): string
    {
        return 'abc';
    }
}
