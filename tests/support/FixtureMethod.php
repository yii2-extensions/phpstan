<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support;

use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

use function file;
use function ltrim;
use function rtrim;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function stripos;

/**
 * Locates the lines of fixture methods, so that expectations name a method and a key instead of a line number.
 */
final class FixtureMethod
{
    /**
     * Returns the name of the fixture method whose declaration contains the line.
     *
     * @phpstan-param class-string $fixture
     */
    public static function at(string $fixture, int|null $line): string
    {
        foreach ((new ReflectionClass($fixture))->getMethods() as $method) {
            if ($line !== null && $line >= $method->getStartLine() && $line <= $method->getEndLine()) {
                return $method->getName();
            }
        }

        throw new RuntimeException(
            sprintf('No method of "%s" contains line %s.', $fixture, $line ?? 'null'),
        );
    }

    /**
     * Returns the first line of a fixture method with the given key followed by `=>`, on the same or the next line, or
     * with its `createObject()` call when the key is `null`.
     *
     * @param string|null $key Key as written in the fixture, such as `'colour'` or `Options::UNKNOWN`.
     *
     * @phpstan-param class-string $fixture
     */
    public static function line(string $fixture, string $method, string|null $key = null): int
    {
        $reflection = new ReflectionMethod($fixture, $method);

        $fileName = $reflection->getFileName();

        $lines = $fileName === false ? false : file($fileName);

        if ($lines !== false) {
            for ($line = (int) $reflection->getStartLine(); $line <= (int) $reflection->getEndLine(); $line++) {
                $source = $lines[$line - 1] ?? '';

                $found = match (true) {
                    $key === null => stripos($source, 'createObject(') !== false,
                    str_contains($source, "{$key} =>") => true,
                    default => str_ends_with(rtrim($source), $key)
                        && str_starts_with(ltrim($lines[$line] ?? ''), '=>'),
                };

                if ($found) {
                    return $line;
                }
            }
        }

        throw new RuntimeException(
            sprintf('Method "%s::%s()" has no line with key %s.', $fixture, $method, $key ?? 'call'),
        );
    }

    /**
     * Returns the public methods that the fixture declares itself.
     *
     * @phpstan-param class-string $fixture
     *
     * @return list<string> Method names in declaration order.
     */
    public static function names(string $fixture): array
    {
        $names = [];

        foreach ((new ReflectionClass($fixture))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() === $fixture) {
                $names[] = $method->getName();
            }
        }

        return $names;
    }
}
