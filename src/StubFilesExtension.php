<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan;

use function file_exists;
use function is_dir;

final class StubFilesExtension implements \PHPStan\PhpDoc\StubFilesExtension
{
    private const APPLICATION_TYPE_STUBS = [
        \yii\base\Application::class => 'BaseYii.stub',
        \yii\console\Application::class => 'BaseyiiConsole.stub',
        \yii\web\Application::class => 'BaseYiiWeb.stub',
    ];

    private const DEFAULT_STUB = 'ApplicationWeb.stub';

    /**
     * @param ServiceMap $serviceMap Service map for determining application type
     */
    public function __construct(private readonly ServiceMap $serviceMap) {}

    public function getFiles(): array
    {
        $stubsDirectory = $this->getStubsDirectory();

        if (is_dir($stubsDirectory) === false) {
            return [];
        }

        $applicationType = $this->serviceMap->getApplicationType();

        $stubFileName = self::APPLICATION_TYPE_STUBS[$applicationType] ?? self::DEFAULT_STUB;
        $stubFilePath = $stubsDirectory . DIRECTORY_SEPARATOR . $stubFileName;

        if (file_exists($stubFilePath) === false) {
            $stubFilePath = $stubsDirectory . DIRECTORY_SEPARATOR . self::DEFAULT_STUB;

            if (file_exists($stubFilePath) === false) {
                return [];
            }
        }

        return [$stubFilePath];
    }

    private function getStubsDirectory(): string
    {
        return dirname(__DIR__) . '/stub';
    }
}
