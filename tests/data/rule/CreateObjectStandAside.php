<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule;

use Yii;
use yii\base\BaseObject;
use yii2\extensions\phpstan\tests\support\createobject\{
    AbstractTarget,
    AliasTarget,
    ChildTarget,
    ComponentTarget,
    CustomConstructorTarget,
    CustomSetterTarget,
    EmptyDefinitionTarget,
    FactoryTarget,
    InstanceTarget,
    OverridingFactory,
    PlainTarget,
    SingletonTarget,
    Target,
    UnresolvedTarget,
};

/**
 * `Yii::createObject()` calls whose target or configuration assignment the rule can't prove.
 */
final class CreateObjectStandAside
{
    /**
     * @param array<string, mixed> $config
     * @param array<array-key, mixed> $extra
     * @param array<array-key, mixed> $params
     * @param class-string<BaseObject> $className
     */
    public function standAside(
        array $config,
        array $extra,
        array $params,
        string $className,
        string $key,
        bool $flag,
        mixed $value,
    ): void {
        Yii::createObject(['class' => FactoryTarget::class, 'colour' => 'factory']);
        Yii::createObject(['class' => InstanceTarget::class, 'colour' => 'object']);
        Yii::createObject(['class' => SingletonTarget::class, 'colour' => 'singleton']);
        Yii::createObject(['class' => AliasTarget::class, 'colour' => 'alias']);
        Yii::createObject(['class' => UnresolvedTarget::class, 'colour' => 'unresolved']);
        Yii::createObject(['class' => EmptyDefinitionTarget::class, 'colour' => 'empty definition']);
        Yii::createObject(['class' => CustomConstructorTarget::class, 'alias' => 'consumed']);
        Yii::createObject(['class' => CustomSetterTarget::class, 'colour' => 'magic']);
        Yii::createObject(['class' => ComponentTarget::class, 'colour' => 'component']);
        Yii::createObject(['class' => AbstractTarget::class, 'colour' => 'abstract']);
        Yii::createObject(['class' => PlainTarget::class, 'colour' => 'plain']);
        Yii::createObject(['class' => 'Missing\Target', 'colour' => 'missing']);
        Yii::createObject(['class' => Target::class, '__construct()' => [[], 'extra'], 'colour' => 'constructor']);
        Yii::createObject(['class' => Target::class, 'colour' => 'params'], [[], 'second']);
        Yii::createObject(['class' => Target::class, 'colour' => 'variable params'], $params);
        Yii::createObject($config);
        Yii::createObject([...$extra, 'class' => Target::class, 'colour' => 'unpacked before']);
        Yii::createObject(['class' => Target::class, 'colour' => 'unpacked after', ...$extra]);
        Yii::createObject(['class' => Target::class, $key => 'variable key']);
        Yii::createObject(['class' => Target::class, 0 => 'integer key', 'colour' => 'integer key']);
        Yii::createObject(['class' => $className, 'colour' => 'class variable']);
        Yii::createObject(['class' => $flag ? Target::class : ChildTarget::class, 'colour' => 'union']);
        Yii::createObject(['class' => $value, 'colour' => 'mixed']);
        Yii::createObject(['__class' => null, 'class' => Target::class, 'colour' => 'null __class']);
        Yii::createObject(['colour' => 'no class']);
        Yii::createObject(['class' => Target::class, 'class' => ComponentTarget::class, 'colour' => 'last class']);
        Yii::createObject(['class' => Target::class, 'colour' => 'unpacked arguments'], ...[$params]);

        OverridingFactory::createObject(['class' => Target::class, 'colour' => 'override']);

        $factory = Yii::createObject(...);

        $factory(['class' => Target::class, 'colour' => 'first-class callable']);
    }
}
