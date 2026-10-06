<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use PHPStan\Analyser\DeclarationDependencyTracker;
use PHPStan\Reflection\ClassReflection;

/**
 * Stub dependency tracker recording each value dependency as declaring class name, extension class and key.
 */
final class RecordingDeclarationDependencyTracker implements DeclarationDependencyTracker
{
    /**
     * @var list<array{string, string, string}>
     */
    public array $dependencies = [];

    public function trackClassDependency(ClassReflection $classReflection, string $className): void {}

    public function trackDirectoryDependency(
        ClassReflection $classReflection,
        string $directory,
        string $pattern = '*',
    ): void {}

    public function trackFileDependency(ClassReflection $classReflection, string $file): void {}

    public function trackValueDependency(ClassReflection $classReflection, string $extensionClass, string $key): void
    {
        $this->dependencies[] = [$classReflection->getName(), $extensionClass, $key];
    }
}
