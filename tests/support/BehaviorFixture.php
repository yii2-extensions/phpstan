<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support;

use ReflectionMethod;
use RuntimeException;

use function file;
use function sprintf;
use function str_contains;

/**
 * Locates lines in methods of behavior definition fixtures, so that expectations name the text a diagnostic belongs to.
 */
final class BehaviorFixture
{
    /**
     * Returns the first line of the method that contains the needle, searching from the first line that contains the
     * start text when one is given.
     *
     * @param string $needle Text of the reported line, such as `'plainClass' =>` or `return $definitions;`.
     * @param string|null $start Text of an earlier line that the search starts from, such as `'multiLine',`.
     *
     * @phpstan-param class-string $class
     */
    public static function line(string $class, string $method, string $needle, string|null $start = null): int
    {
        $reflection = new ReflectionMethod($class, $method);

        $fileName = $reflection->getFileName();

        $lines = $fileName === false ? false : file($fileName);

        $started = $start === null;

        if ($lines !== false) {
            for ($line = (int) $reflection->getStartLine(); $line <= (int) $reflection->getEndLine(); $line++) {
                $source = $lines[$line - 1] ?? '';

                $started = $started || str_contains($source, (string) $start);

                if ($started && str_contains($source, $needle)) {
                    return $line;
                }
            }
        }

        throw new RuntimeException(
            sprintf('`%s::%s()` has no line with `%s`.', $class, $method, $needle),
        );
    }
}
