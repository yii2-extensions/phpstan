<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\reflection;

use PHPStan\Reflection\{ClassReflection, ReflectionProvider};
use RuntimeException;
use yii\base\Application;
use yii2\extensions\phpstan\exception\Message;

use function ltrim;

/**
 * Validates the configured application type against PHPStan reflection and resolves its class reflection.
 *
 * The application type is validated where the analysis consumes it, not when the configuration is loaded: PHPStan
 * executes the configuration before `parameters.bootstrapFiles`, and only its reflection also discovers classes from
 * analysed paths and scanned files. A class PHPStan reflection can't discover, an interface, a trait, an enum, or a class
 * not extending {@see Application} is rejected; {@see Application} itself and every subclass are accepted.
 *
 * Project stub validation reflects stub files only, where {@see Application} is usually unknown and the application
 * hierarchy can't be established. When {@see Application} can't be reflected, the class is returned unvalidated if it
 * can be reflected, or `null` otherwise, as before validation existed.
 */
final class ApplicationClassResolver
{
    /**
     * Returns the class reflection of the configured application type.
     *
     * @param string $applicationType Application type returned by {@see \yii2\extensions\phpstan\ServiceMap}, with or
     * without a leading backslash.
     * @param ReflectionProvider $reflectionProvider Reflection provider of the running analysis.
     *
     * @throws RuntimeException if {@see Application} can be reflected and the application type doesn't name it or a
     * class extending it.
     *
     * @return ClassReflection|null Class reflection of the application type, or `null` if neither {@see Application}
     * nor the application type can be reflected.
     */
    public static function resolve(string $applicationType, ReflectionProvider $reflectionProvider): ClassReflection|null
    {
        $className = ltrim($applicationType, '\\');

        if ($reflectionProvider->hasClass(Application::class) === false) {
            return $reflectionProvider->hasClass($className) ? $reflectionProvider->getClass($className) : null;
        }

        if ($reflectionProvider->hasClass($className) === false) {
            self::throwInvalid($applicationType, 'an unknown class');
        }

        $classReflection = $reflectionProvider->getClass($className);

        $invalidKind = match (true) {
            $classReflection->isInterface() => 'an interface',
            $classReflection->isTrait() => 'a trait',
            $classReflection->isEnum() => 'an enum',
            $classReflection->is(Application::class) => null,
            default => "a class that doesn't extend 'yii\\base\\Application'",
        };

        if ($invalidKind !== null) {
            self::throwInvalid($applicationType, $invalidKind);
        }

        return $classReflection;
    }

    /**
     * Throws a {@see RuntimeException} describing why the application type is rejected.
     *
     * @param string $applicationType Configured application type.
     * @param string $kind Description of what the application type names.
     *
     * @throws RuntimeException always.
     */
    private static function throwInvalid(string $applicationType, string $kind): never
    {
        throw new RuntimeException(
            Message::APPLICATION_TYPE_NOT_APPLICATION_CLASS->getMessage($applicationType, $kind),
        );
    }
}
