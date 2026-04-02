<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\StubFilesExtension;

use function file_get_contents;
use function glob;
use function sys_get_temp_dir;
use function unlink;

/**
 * Unit tests for {@see StubFilesExtension} dynamic stub file generation.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.4.1
 */
final class StubFilesExtensionTest extends TestCase
{
    /**
     * Tracked stub file paths generated during tests, cleaned up in tearDown.
     *
     * @phpstan-var string[]
     */
    private array $generatedStubs = [];

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

    public function testGetFilesReturnsStubForBaseApplicationType(): void
    {
        $this->cleanGeneratedStubs();

        $ds = DIRECTORY_SEPARATOR;
        $configPath = __DIR__ . "{$ds}config{$ds}phpstan-base-app-config.php";

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

        $stubContent = (string) file_get_contents($stubPath);

        self::assertStringContainsString(
            '@var \yii\base\Application',
            $stubContent,
            "Stub should declare 'Yii::\$app' as '\yii\base\Application' for base configuration.",
        );
        self::assertStringNotContainsString(
            'class Application extends',
            $stubContent,
            'Stub should not declare a child application class for the base application type.',
        );

        $this->generatedStubs[] = $stubPath;
    }

    public function testGetFilesReturnsStubForConsoleApplicationType(): void
    {
        $this->cleanGeneratedStubs();

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

        $this->generatedStubs[] = $stubPath;
    }

    public function testGetFilesReturnsStubForCustomApplicationType(): void
    {
        $this->cleanGeneratedStubs();

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

        $this->generatedStubs[] = $stubPath;
    }

    public function testGetFilesReturnsStubForDefaultApplicationType(): void
    {
        $this->cleanGeneratedStubs();

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

        $this->generatedStubs[] = $stubPath;
    }

    public function testGetFilesReturnsStubForGlobalNamespaceApplicationType(): void
    {
        $this->cleanGeneratedStubs();

        $ds = DIRECTORY_SEPARATOR;
        $configPath = __DIR__ . "{$ds}config{$ds}phpstan-global-class-app-config.php";

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

        $stubContent = (string) file_get_contents($stubPath);

        self::assertStringContainsString(
            '@var \GlobalApplication',
            $stubContent,
            "Stub should declare 'Yii::\$app' as '\GlobalApplication' for global namespace configuration.",
        );
        self::assertStringContainsString(
            'class GlobalApplication extends \yii\base\Application {}',
            $stubContent,
            'Stub should declare the global namespace application class extending base Application.',
        );

        $this->generatedStubs[] = $stubPath;
    }

    public function testGetFilesReturnsStubForWebApplicationType(): void
    {
        $this->cleanGeneratedStubs();

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

        $this->generatedStubs[] = $stubPath;
    }

    public function testThrowExceptionWhenStubFileCannotBeWritten(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $nonWritableDir = $ds . 'nonexistent' . $ds . 'directory' . $ds . 'path';

        $stubFilesExtension = new StubFilesExtension(new ServiceMap(), $nonWritableDir);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Ensure the temporary directory is writable.');

        $stubFilesExtension->getFiles();
    }

    protected function tearDown(): void
    {
        foreach ($this->generatedStubs as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $this->generatedStubs = [];
    }

    /**
     * Removes all generated PHPStan stub files from the temporary directory.
     */
    private function cleanGeneratedStubs(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $pattern = sys_get_temp_dir() . "{$ds}yii2-phpstan-stub-*.stub";

        $files = glob($pattern);

        foreach ($files !== false ? $files : [] as $file) {
            unlink($file);
        }
    }
}
