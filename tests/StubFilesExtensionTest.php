<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\StubFilesExtension;

use function file_get_contents;

/**
 * Unit tests for {@see StubFilesExtension} dynamic stub file generation.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.4.1
 */
final class StubFilesExtensionTest extends TestCase
{
    public function testGeneratedStubIsCached(): void
    {
        $stubFilesExtension = new StubFilesExtension(new ServiceMap());

        $firstCall = $stubFilesExtension->getFiles();
        $secondCall = $stubFilesExtension->getFiles();

        self::assertSame(
            $firstCall,
            $secondCall,
            'Should return the same cached stub file path.',
        );
    }

    public function testGetFilesReturnsStubForConsoleApplicationType(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $configPath = __DIR__ . "{$ds}config{$ds}phpstan-console-config.php";

        $stubFilesExtension = new StubFilesExtension(new ServiceMap($configPath));

        $files = $stubFilesExtension->getFiles();

        self::assertCount(
            1,
            $files,
            'Should return exactly one stub file.',
        );

        $stubPath = $files[0] ?? null;

        self::assertNotNull(
            $stubPath,
            "Stub file path should not be 'null'.",
        );
        self::assertFileExists(
            $stubPath,
            'Generated stub file should exist on disk.',
        );
        self::assertStringContainsString(
            '@var \yii\console\Application',
            (string) file_get_contents($stubPath),
            "Stub should declare 'Yii::\$app' as '\yii\console\Application' for console configuration.",
        );
    }

    public function testGetFilesReturnsStubForCustomApplicationType(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $configPath = __DIR__ . "{$ds}config{$ds}phpstan-custom-app-config.php";

        $stubFilesExtension = new StubFilesExtension(new ServiceMap($configPath));

        $files = $stubFilesExtension->getFiles();

        self::assertCount(
            1,
            $files,
            'Should return exactly one stub file.',
        );

        $stubPath = $files[0] ?? null;

        self::assertNotNull(
            $stubPath,
            "Stub file path should not be 'null'.",
        );
        self::assertFileExists(
            $stubPath,
            'Generated stub file should exist on disk.',
        );
        self::assertStringContainsString(
            '@var \yii2\extensions\phpstan\tests\support\stub\ApplicationCustom',
            (string) file_get_contents($stubPath),
            "Stub should declare 'Yii::\$app' as '\yii2\extensions\phpstan\tests\support\stub\ApplicationCustom'"
            . ' for custom configuration.',
        );
    }

    public function testGetFilesReturnsStubForDefaultApplicationType(): void
    {
        $stubFilesExtension = new StubFilesExtension(new ServiceMap());

        $files = $stubFilesExtension->getFiles();

        self::assertCount(
            1,
            $files,
            'Should return exactly one stub file.',
        );

        $stubPath = $files[0] ?? null;

        self::assertNotNull(
            $stubPath,
            "Stub file path should not be 'null'.",
        );
        self::assertFileExists(
            $stubPath,
            'Generated stub file should exist on disk.',
        );
        self::assertStringContainsString(
            '@var \yii\web\Application',
            (string) file_get_contents($stubPath),
            "Stub should default to '\yii\web\Application' when no configuration is provided.",
        );
    }

    public function testGetFilesReturnsStubForWebApplicationType(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $configPath = __DIR__ . "{$ds}config{$ds}phpstan-config.php";

        $stubFilesExtension = new StubFilesExtension(new ServiceMap($configPath));

        $files = $stubFilesExtension->getFiles();

        self::assertCount(
            1,
            $files,
            'Should return exactly one stub file.',
        );

        $stubPath = $files[0] ?? null;

        self::assertNotNull(
            $stubPath,
            "Stub file path should not be 'null'.",
        );
        self::assertFileExists(
            $stubPath,
            'Generated stub file should exist on disk.',
        );
        self::assertStringContainsString(
            '@var \yii\web\Application',
            (string) file_get_contents($stubPath),
            "Stub should declare 'Yii::\$app' as '\yii\web\Application' for web configuration.",
        );
    }
}
