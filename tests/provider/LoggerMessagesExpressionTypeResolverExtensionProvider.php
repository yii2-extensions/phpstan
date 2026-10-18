<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\type\LoggerMessagesExpressionTypeResolverExtensionTest;

/**
 * Type assertion datasets for {@see LoggerMessagesExpressionTypeResolverExtensionTest}.
 */
final class LoggerMessagesExpressionTypeResolverExtensionProvider
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        $directory = dirname(__DIR__);

        yield from LoggerMessagesExpressionTypeResolverExtensionTest::gatherAssertTypes(
            "{$directory}/data/type/LoggerMessagesExpressionTypeResolver.php",
        );
    }
}
