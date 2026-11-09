<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support;

use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPUnit\Framework\MockObject\Stub;

/**
 * Provides a {@see Scope} double whose {@see DependencyTracker} calls are recorded in call order.
 */
trait DependencyRecordingScopeTrait
{
    /**
     * Class names passed to `trackClassDependency()`.
     *
     * @var list<string>
     */
    private array $trackedClasses = [];
    /**
     * Extension class and key pairs passed to `trackValueDependency()`.
     *
     * @var list<array{string, string}>
     */
    private array $trackedValues = [];

    private function recordingScope(): DependencyTracker&Scope&Stub
    {
        /** @var DependencyTracker&Scope&Stub $scope */
        $scope = self::createStubForIntersectionOfInterfaces([Scope::class, DependencyTracker::class]);

        $scope
            ->method('trackClassDependency')
            ->willReturnCallback(
                function (string $className): void {
                    $this->trackedClasses[] = $className;
                },
            );
        $scope
            ->method('trackValueDependency')
            ->willReturnCallback(
                function (string $extensionClass, string $key): void {
                    $this->trackedValues[] = [$extensionClass, $key];
                },
            );

        return $scope;
    }
}
