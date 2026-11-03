<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use Yii;
use yii2\extensions\phpstan\tests\support\createobject\{AliasTarget, BindingContract, EmptyDefinitionTarget};

/**
 * `Yii::createObject()` calls of container-bound IDs for both option rules, one per method.
 */
final class CreateObjectContainerBinding
{
    public function abstractTarget(): object
    {
        return Yii::createObject(['class' => 'binding.abstract', 'colour' => 'red', 'name' => []]);
    }

    public function aliasToFactory(): object
    {
        return Yii::createObject(['class' => 'binding.toFactory', 'colour' => 'red', 'name' => []]);
    }

    public function aliasToSingleton(): object
    {
        return Yii::createObject(['class' => 'binding.toSingleton', 'colour' => 'red', 'name' => []]);
    }

    public function arrayClassDefinition(): object
    {
        return Yii::createObject(['class' => 'binding.array', 'colour' => 'red']);
    }

    public function arrayLegacyClassDefinition(): object
    {
        return Yii::createObject(['class' => 'binding.legacy', 'colour' => 'red']);
    }

    public function classAlias(): object
    {
        return Yii::createObject(['class' => AliasTarget::class, 'colour' => 'red']);
    }

    public function classAndLegacyClassDefinition(): object
    {
        return Yii::createObject(['class' => 'binding.classes', 'colour' => 'red', 'name' => []]);
    }

    public function componentAlias(): object
    {
        return Yii::createObject(['class' => 'binding.component', 'colour' => 'red']);
    }

    public function componentAliasInvalidValue(): object
    {
        return Yii::createObject(['class' => 'binding.component', 'count' => 'abc']);
    }

    public function configuredFactory(): object
    {
        return Yii::createObject(['class' => 'binding.configuredFactory', 'colour' => 'red', 'name' => []]);
    }

    public function constantConfigurationThroughChain(): object
    {
        $config = ['class' => 'binding.first', 'colour' => 'red'];

        return Yii::createObject($config);
    }

    public function constructorParams(): object
    {
        return Yii::createObject(['class' => 'binding.params', 'colour' => 'red', 'name' => []]);
    }

    public function customConstructor(): object
    {
        return Yii::createObject(['class' => 'binding.customConstructor', 'alias' => 'consumed', 'name' => []]);
    }

    public function cycle(): object
    {
        return Yii::createObject(['class' => 'binding.cycleFirst', 'colour' => 'red', 'name' => []]);
    }

    public function definitionOptions(): object
    {
        return Yii::createObject(['class' => 'binding.options', 'colour' => 'red', 'name' => []]);
    }

    public function emptyDefinition(): object
    {
        return Yii::createObject(['class' => EmptyDefinitionTarget::class, 'colour' => 'red']);
    }

    public function emptyDefinitionInvalidValue(): object
    {
        return Yii::createObject(['class' => EmptyDefinitionTarget::class, 'name' => []]);
    }

    public function emptyParamsDefinition(): object
    {
        return Yii::createObject(['class' => 'binding.wrapped', 'colour' => 'red']);
    }

    public function factory(): object
    {
        return Yii::createObject(['class' => 'binding.factory', 'colour' => 'red', 'name' => []]);
    }

    public function instanceDefinition(): object
    {
        return Yii::createObject(['class' => 'binding.instance', 'colour' => 'red']);
    }

    public function interfaceAlias(): object
    {
        return Yii::createObject(['class' => BindingContract::class, 'colour' => 'red']);
    }

    public function interfaceAliasInvalidValue(): object
    {
        return Yii::createObject(['class' => BindingContract::class, 'name' => []]);
    }

    public function leadingBackslashTargetSkipsFactory(): object
    {
        return Yii::createObject(['class' => 'binding.backslash', 'colour' => 'red']);
    }

    public function mappedComponentAlias(): object
    {
        return Yii::createObject(['class' => 'binding.mapped', 'colour' => 'red']);
    }

    public function missingTarget(): object
    {
        return Yii::createObject(['class' => 'binding.missing', 'colour' => 'red', 'name' => []]);
    }

    public function object(): object
    {
        return Yii::createObject(['class' => 'binding.object', 'colour' => 'red', 'name' => []]);
    }

    public function paramsOnLaterHop(): object
    {
        return Yii::createObject(['class' => 'binding.paramsHop', 'colour' => 'red', 'name' => []]);
    }

    public function replacedBySingleton(): object
    {
        return Yii::createObject(['class' => 'binding.replaced', 'colour' => 'red', 'name' => []]);
    }

    public function singleton(): object
    {
        return Yii::createObject(['class' => 'binding.singleton', 'colour' => 'red', 'name' => []]);
    }

    public function threeHopChain(): object
    {
        return Yii::createObject(['class' => 'binding.first', 'colour' => 'red']);
    }

    public function threeHopChainInvalidValue(): object
    {
        return Yii::createObject(['class' => 'binding.first', 'name' => []]);
    }

    public function validOptionsThroughChain(): object
    {
        return Yii::createObject(['class' => 'binding.first', 'name' => 'valid', 'label' => 'setter']);
    }

    public function zeroDefinition(): object
    {
        return Yii::createObject(['class' => 'binding.zero', 'colour' => 'red', 'name' => []]);
    }
}
