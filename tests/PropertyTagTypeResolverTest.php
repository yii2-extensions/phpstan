<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\PropertyTagTypeResolver;
use yii2\extensions\phpstan\tests\provider\PropertyTagTypeResolverProvider;
use yii2\extensions\phpstan\tests\support\stub\{ArticleDraft, Book};

use function array_map;
use function ksort;

/**
 * Unit tests for {@see PropertyTagTypeResolver} collection of declared and inherited `@property` tags.
 *
 * {@see PropertyTagTypeResolverProvider} for test case data providers.
 */
final class PropertyTagTypeResolverTest extends PHPStanTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/support/extension-test.neon'];
    }

    public function testGetReadableTagsCollectsNearestReadableDeclarations(): void
    {
        $tags = self::createResolver()->getReadableTags(self::getClassReflection(ArticleDraft::class));

        self::assertSame(
            [
                'title' => ['string|null', true],
                'note' => ['string', true],
                'revision' => ['int', true],
                'id' => ['int', true],
                'slug' => ['string', true],
            ],
            array_map(
                static fn(array $tag): array => [$tag['type']->describe(VerbosityLevel::precise()), $tag['writable']],
                $tags,
            ),
            'Order, types, and writability must follow PHPStan precedence.',
        );
    }

    public function testGetReadableTagsFlagsReadOnlyTagsAsNotWritable(): void
    {
        $writability = array_map(
            static fn(array $tag): bool => $tag['writable'],
            self::createResolver()->getReadableTags(self::getClassReflection(Book::class)),
        );

        ksort($writability);

        self::assertSame(
            [
                'author' => false,
                'category' => true,
                'comments' => false,
                'id' => true,
                'label' => false,
                'title' => true,
            ],
            $writability,
            'Writability must follow the tag variant.',
        );
    }

    /**
     * @phpstan-param class-string $className
     */
    #[DataProviderExternal(PropertyTagTypeResolverProvider::class, 'readableTypeProvider')]
    public function testGetReadableTypeResolvesNearestTag(
        string $className,
        string $propertyName,
        string|null $expectedType,
    ): void {
        $type = self::createResolver()->getReadableType(self::getClassReflection($className), $propertyName);

        self::assertSame(
            $expectedType,
            $type?->describe(VerbosityLevel::precise()),
            'Readable type must match.',
        );
    }

    /**
     * Creates a resolver of its own, since the container instance is already used by the type inference data providers,
     * which run before code coverage collection starts.
     */
    private static function createResolver(): PropertyTagTypeResolver
    {
        return new PropertyTagTypeResolver(
            self::getContainer()->getByType(AnnotationsPropertiesClassReflectionExtension::class),
            self::createReflectionProvider(),
        );
    }

    /**
     * @phpstan-param class-string $className
     */
    private static function getClassReflection(string $className): ClassReflection
    {
        return self::createReflectionProvider()->getClass($className);
    }
}
