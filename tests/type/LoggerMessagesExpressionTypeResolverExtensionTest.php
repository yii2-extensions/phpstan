<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\tests\provider\LoggerMessagesExpressionTypeResolverExtensionProvider;

/**
 * Tests direct PHPStan expression inference for Yii logger and target message arrays.
 *
 * {@see LoggerMessagesExpressionTypeResolverExtensionProvider} for type assertion datasets.
 */
final class LoggerMessagesExpressionTypeResolverExtensionTest extends TypeInferenceTestCase
{
    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-test.neon'];
    }

    #[DataProviderExternal(LoggerMessagesExpressionTypeResolverExtensionProvider::class, 'dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
