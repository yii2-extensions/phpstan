<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\data\rule\WidgetInvalidOptionType;
use yii2\extensions\phpstan\tests\support\createobject\TypedValue;
use yii2\extensions\phpstan\tests\support\widget\{BoundWidget, ChildWidget, OtherWidget, TargetWidget};

use function sprintf;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\WidgetInvalidOptionTypeRuleTest} test cases.
 */
final class WidgetInvalidOptionTypeRuleProvider
{
    /**
     * @phpstan-return iterable<string, array{class-string, string, list<array{string|null, string}>}>
     */
    public static function reportedProvider(): iterable
    {
        $fixture = WidgetInvalidOptionType::class;
        $target = TargetWidget::class;

        $setter = "{$target}::setLabel()";

        $child = ChildWidget::class;

        yield 'array for string property' => [
            $fixture,
            'arrayForStringProperty',
            [
                [
                    "'name'",
                    self::message(
                        $target,
                        'begin',
                        $target,
                        'name',
                        'array{}',
                        'the property',
                        'string',
                    ),
                ],
            ],
        ];
        yield 'array for string setter' => [
            $fixture,
            'arrayForStringSetter',
            [
                [
                    "'label'",
                    self::message(
                        $target,
                        'widget',
                        $target,
                        'label',
                        'array{}',
                        $setter,
                        'string',
                    ),
                ],
            ],
        ];
        yield 'container substitution' => [
            $fixture,
            'boundClass',
            [
                [
                    "'colour'",
                    self::message(
                        BoundWidget::class,
                        'begin',
                        OtherWidget::class,
                        'colour',
                        'array{}',
                        'the property',
                        'string',
                    ),
                ],
            ],
        ];
        yield 'constant configuration' => [
            $fixture,
            'constantConfiguration',
            [
                [
                    null,
                    self::message(
                        $target,
                        'widget',
                        $target,
                        'count',
                        "'all'",
                        'the property',
                        'int',
                    ),
                ],
            ],
        ];
        yield 'constructor argument' => [
            $fixture,
            'constructArgument',
            [
                [
                    "'count'",
                    self::message(
                        $target,
                        'widget',
                        $target,
                        'count',
                        "'all'",
                        'the property',
                        'int',
                    ),
                ],
            ],
        ];
        yield 'inherited property' => [
            $fixture,
            'inheritedProperty',
            [
                [
                    "'count'",
                    self::message(
                        $child,
                        'widget',
                        $child,
                        'count',
                        "'all'",
                        'the property',
                        'int',
                    ),
                ],
            ],
        ];
        yield 'legacy class key' => [
            $fixture,
            'legacyClassKey',
            [
                [
                    "'colour'",
                    self::message(
                        $target,
                        'widget',
                        OtherWidget::class,
                        'colour',
                        'array{}',
                        'the property',
                        'string',
                    ),
                ],
            ],
        ];
        yield 'named config' => [
            $fixture,
            'namedConfig',
            [
                [
                    "'count'",
                    self::message(
                        $target,
                        'begin',
                        $target,
                        'count',
                        'array{}',
                        'the property',
                        'int',
                    ),
                ],
            ],
        ];
        yield 'non-numeric string for int property' => [
            $fixture,
            'nonNumericStringForIntProperty',
            [
                [
                    "'count'",
                    self::message(
                        $target,
                        'widget',
                        $target,
                        'count',
                        "'all'",
                        'the property',
                        'int',
                    ),
                ],
            ],
        ];
        yield 'object for class property' => [
            $fixture,
            'objectForClassProperty',
            [
                [
                    "'value'",
                    self::message(
                        $target,
                        'begin',
                        $target,
                        'value',
                        OtherWidget::class,
                        'the property',
                        TypedValue::class . '|null',
                    ),
                ],
            ],
        ];
    }

    /**
     * Returns the expected message for a value that the created widget never accepts.
     */
    private static function message(
        string $calledClass,
        string $method,
        string $class,
        string $option,
        string $value,
        string $member,
        string $expected,
    ): string {
        return sprintf(
            '%s::%s() sets %s::$%s to %s, but %s accepts only %s, even with type coercion.',
            $calledClass,
            $method,
            $class,
            $option,
            $value,
            $member,
            $expected,
        );
    }
}
