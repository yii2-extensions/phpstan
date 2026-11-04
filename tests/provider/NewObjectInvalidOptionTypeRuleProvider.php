<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii\di\Instance;
use yii2\extensions\phpstan\tests\data\rule\NewObjectInvalidOptionType;
use yii2\extensions\phpstan\tests\support\createobject\{
    ComponentMembersTarget,
    TypedChildTarget,
    TypedPlain,
    TypedTarget,
    TypedValue,
};

use function sprintf;
use function str_replace;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\NewObjectInvalidOptionTypeRuleTest} test cases.
 */
final class NewObjectInvalidOptionTypeRuleProvider
{
    /**
     * @phpstan-return iterable<string, array{class-string, string, list<array{string|null, string}>}>
     */
    public static function reportedProvider(): iterable
    {
        $fixture = NewObjectInvalidOptionType::class;
        $typed = TypedTarget::class;

        $quotedTyped = "'" . str_replace('\\', '\\\\', $typed) . "'";

        yield 'array for string setter' => [
            $fixture,
            'arrayForStringSetter',
            [["'text'", self::message($typed, 'text', 'array{}', "{$typed}::setText()", 'string')]],
        ];
        yield 'class key for int setter' => [
            $fixture,
            'classKeyForIntSetter',
            [["'class'", self::message($typed, 'class', $quotedTyped, "{$typed}::setClass()", 'int')]],
        ];
        yield 'component property' => [
            $fixture,
            'componentProperty',
            [["'count'", self::message(ComponentMembersTarget::class, 'count', "'all'", 'the property', 'int')]],
        ];
        yield 'constant configuration' => [
            $fixture,
            'constantConfiguration',
            [[null, self::message($typed, 'count', "'all'", 'the property', 'int')]],
        ];
        yield 'inherited property' => [
            $fixture,
            'inheritedProperty',
            [["'count'", self::message(TypedChildTarget::class, 'count', "'all'", 'the property', 'int')]],
        ];
        yield 'instance for class property' => [
            $fixture,
            'instanceForClassProperty',
            [["'value'", self::message($typed, 'value', Instance::class, 'the property', TypedValue::class . '|null')]],
        ];
        yield 'named config' => [
            $fixture,
            'namedConfig',
            [["'count'", self::message($typed, 'count', 'array{}', 'the property', 'int')]],
        ];
        yield 'non-numeric string for int property' => [
            $fixture,
            'nonNumericStringForIntProperty',
            [["'count'", self::message($typed, 'count', "'all'", 'the property', 'int')]],
        ];
        yield 'null for string property' => [
            $fixture,
            'nullForStringProperty',
            [["'title'", self::message($typed, 'title', 'null', 'the property', 'string')]],
        ];
        yield 'object for int property' => [
            $fixture,
            'objectForIntProperty',
            [["'count'", self::message($typed, 'count', TypedPlain::class, 'the property', 'int')]],
        ];
    }

    /**
     * Returns the diagnostic message for a value that the property or setter never accepts.
     */
    private static function message(
        string $class,
        string $name,
        string $actual,
        string $member,
        string $expected,
    ): string {
        return sprintf(
            'new %s() sets $%s to %s, but %s accepts only %s, even with type coercion.',
            $class,
            $name,
            $actual,
            $member,
            $expected,
        );
    }
}
