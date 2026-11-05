<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use stdClass;
use yii\base\{Action, BaseObject, Component, Configurable};
use yii2\extensions\phpstan\tests\support\action\{
    AbstractAction,
    ChildAction,
    ExtraParamConstructorAction,
    ForwardingConstructorAction,
    PlainObject,
    TargetAction,
};
use yii2\extensions\phpstan\tests\support\createobject\{CustomConstructorTarget, Target};

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\CreateObjectConstructionContractTest} test cases.
 */
final class CreateObjectConstructionContractProvider
{
    /**
     * @phpstan-return iterable<string, array{class-string, bool, bool}>
     */
    public static function classProvider(): iterable
    {
        yield 'BaseObject' => [
            BaseObject::class,
            true,
            false,
        ];
        yield 'BaseObject subclass' => [
            Target::class,
            true,
            false,
        ];
        yield 'Component' => [
            Component::class,
            true,
            false,
        ];
        yield 'BaseObject subclass with a custom constructor' => [
            CustomConstructorTarget::class,
            false,
            false,
        ];
        yield 'Action' => [
            Action::class,
            false,
            true,
        ];
        yield 'Action subclass' => [
            TargetAction::class,
            false,
            true,
        ];
        yield 'indirect Action subclass' => [
            ChildAction::class,
            false,
            true,
        ];
        yield 'abstract Action subclass' => [
            AbstractAction::class,
            false,
            false,
        ];
        yield 'Action subclass with a forwarding constructor' => [
            ForwardingConstructorAction::class,
            false,
            false,
        ];
        yield 'Action subclass with an extra constructor parameter' => [
            ExtraParamConstructorAction::class,
            false,
            false,
        ];
        yield 'class without a constructor' => [
            PlainObject::class,
            false,
            false,
        ];
        yield 'internal class without a constructor' => [
            stdClass::class,
            false,
            false,
        ];
        yield 'interface' => [
            Configurable::class,
            false,
            false,
        ];
    }
}
