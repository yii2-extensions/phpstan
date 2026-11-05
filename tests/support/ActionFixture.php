<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support;

use ReflectionMethod;
use RuntimeException;

use function file;
use function sprintf;
use function str_contains;

/**
 * Locates lines of the `actions()` method of fixture controllers, so that expectations name an action ID and a key.
 */
final class ActionFixture
{
    /**
     * Returns the first `actions()` line with the key followed by `=>` after the line of the action ID, the line of the
     * action ID when the key is `null`, or the first `return` line when the action ID is `null`.
     *
     * @param string|null $actionId Action ID as written, without quotes.
     * @param string|null $key Key as written in the fixture, such as `'colour'` or `self::OPTION`.
     *
     * @phpstan-param class-string $controller
     */
    public static function line(string $controller, string|null $actionId, string|null $key = null): int
    {
        $reflection = new ReflectionMethod($controller, 'actions');

        $fileName = $reflection->getFileName();

        $lines = $fileName === false ? false : file($fileName);

        $start = $actionId === null ? 'return ' : "'{$actionId}' =>";
        $started = false;

        if ($lines !== false) {
            for ($line = (int) $reflection->getStartLine(); $line <= (int) $reflection->getEndLine(); $line++) {
                $source = $lines[$line - 1] ?? '';

                $started = $started || str_contains($source, $start);

                if ($started && ($key === null || str_contains($source, "{$key} =>"))) {
                    return $line;
                }
            }
        }

        throw new RuntimeException(
            sprintf('`%s::actions()` has no line for %s and key %s.', $controller, $start, $key ?? 'null'),
        );
    }
}
