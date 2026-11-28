<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support;

use ReflectionMethod;

use function str_contains;

/**
 * Reads declarations of the installed Yii version that change what PHPStan reports on its own.
 */
final class YiiDeclaration
{
    /**
     * Returns whether the declared return type of the method requires a `class` element in configuration arrays.
     *
     * @phpstan-param class-string $class
     */
    public static function requiresClassElement(string $class, string $method): bool
    {
        $comment = (new ReflectionMethod($class, $method))->getDocComment();

        return $comment === false || str_contains($comment, 'class?:') === false;
    }
}
