<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use function is_string;

/**
 * Tests direct PHPStan expression inference for Yii logger and target message arrays.
 */
final class LoggerMessagesExpressionTypeResolverExtensionTest extends TypeInferenceTestCase
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from self::gatherAssertTypes(
            "{$directory}/data/type/LoggerMessagesExpressionTypeResolver.php",
        );
    }

    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/support/extension-test.neon'];
    }

    #[DataProvider('dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        if (
            $assertType === 'type'
            && !class_exists('yii\\log\\PsrMessage')
            && is_string($args[0] ?? null)
        ) {
            $args[0] = str_replace('|yii\\log\\PsrMessage', '', $args[0]);
        }

        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
