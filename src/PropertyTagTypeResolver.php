<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan;

use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\{ClassReflection, ReflectionProvider};
use PHPStan\Type\Type;
use yii\db\ActiveRecord;

use function array_fill_keys;
use function array_keys;

/**
 * Resolves the readable types of the class-level `@property` tags that a class declares or inherits.
 *
 * Tags are collected from the class, the traits it uses, its parent classes, and its interfaces, in the order PHPStan
 * applies to annotation properties, so the nearest declaration of a name wins, and a write-only nearest declaration
 * hides the name. Template types are resolved against the declaring ancestor, so `@property T $value` read through
 * `@extends Base<float>` yields `float`; tags declared by traits keep their template types, as in PHPStan. Tags
 * declared by {@see ActiveRecord} and the classes, interfaces, and traits it is built from are skipped, because they
 * describe framework properties such as `isNewRecord` rather than attributes.
 *
 * {@see AnnotationsPropertiesClassReflectionExtension} for the PHPStan precedence and template type resolution.
 */
final class PropertyTagTypeResolver
{
    /**
     * @phpstan-var array<string, true>|null
     */
    private array|null $frameworkClassNames = null;

    /**
     * @param AnnotationsPropertiesClassReflectionExtension $annotationsProperties PHPStan extension used to resolve the
     * template types of a tag against its declaring class.
     * @param ReflectionProvider $reflectionProvider Reflection provider used to collect the framework classes to skip.
     */
    public function __construct(
        private readonly AnnotationsPropertiesClassReflectionExtension $annotationsProperties,
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    /**
     * Returns the readable type and the writability of all `@property` tags declared or inherited by the class.
     *
     * A tag is writable when its nearest declaration is `@property`, and read-only when it is the `-read` variant.
     *
     * @param ClassReflection $classReflection Reflection of the class whose tags are read.
     *
     * @return array<string, array{type: Type, writable: bool}> Readable property type and writability indexed by
     * property name, nearest declarations first.
     */
    public function getReadableTags(ClassReflection $classReflection): array
    {
        $readableTags = [];

        $declarations = $this->collectDeclarations($classReflection, []);

        foreach ($declarations as $propertyName => [$declaringClass, $readableType, $writable]) {
            $resolvedType = $this->resolveReadableType($propertyName, $declaringClass, $readableType);

            if ($resolvedType !== null) {
                $readableTags[$propertyName] = ['type' => $resolvedType, 'writable' => $writable];
            }
        }

        return $readableTags;
    }

    /**
     * Returns the readable type of a single `@property` tag declared or inherited by the class.
     *
     * @param ClassReflection $classReflection Reflection of the class whose tags are read.
     * @param string $propertyName Name of the property to resolve.
     *
     * @return Type|null Readable property type, or `null` when no tag declares the property or the nearest one is
     * write-only.
     */
    public function getReadableType(ClassReflection $classReflection, string $propertyName): Type|null
    {
        $declaration = $this->collectDeclarations($classReflection, [])[$propertyName] ?? null;

        if ($declaration === null) {
            return null;
        }

        [$declaringClass, $readableType] = $declaration;

        return $this->resolveReadableType($propertyName, $declaringClass, $readableType);
    }

    /**
     * Maps each tagged property name to its nearest declaration in the class, its traits, parents, and interfaces.
     *
     * @param ClassReflection $classReflection Class, trait, or interface to visit.
     * @param array<string, array{ClassReflection, Type|null, bool}> $declarations Declarations already collected from
     * nearer classes.
     *
     * @return array<string, array{ClassReflection, Type|null, bool}> Declaring class, unresolved readable type (`null`
     * for a write-only tag), and writability indexed by property name.
     */
    private function collectDeclarations(ClassReflection $classReflection, array $declarations): array
    {
        if (isset($this->getFrameworkClassNames()[$classReflection->getName()])) {
            return $declarations;
        }

        foreach ($classReflection->getPropertyTags() as $propertyName => $propertyTag) {
            $declarations[$propertyName] ??= [
                $classReflection,
                $propertyTag->getReadableType(),
                $propertyTag->isWritable(),
            ];
        }

        foreach ($classReflection->getTraits() as $traitReflection) {
            $declarations = $this->collectDeclarations($traitReflection, $declarations);
        }

        $parentClass = $classReflection->getParentClass();

        if ($parentClass !== null) {
            $declarations = $this->collectDeclarations($parentClass, $declarations);
        }

        foreach ($classReflection->getInterfaces() as $interfaceReflection) {
            $declarations = $this->collectDeclarations($interfaceReflection, $declarations);
        }

        return $declarations;
    }

    /**
     * Returns the names of {@see ActiveRecord}, its parent classes, interfaces, and traits.
     *
     * @return array<string, true> Framework class names used as keys.
     */
    private function getFrameworkClassNames(): array
    {
        if ($this->frameworkClassNames !== null) {
            return $this->frameworkClassNames;
        }

        $classNames = [];

        if ($this->reflectionProvider->hasClass(ActiveRecord::class)) {
            $activeRecord = $this->reflectionProvider->getClass(ActiveRecord::class);
            $classNames = [
                $activeRecord->getName(),
                ...$activeRecord->getParentClassesNames(),
                ...array_keys($activeRecord->getInterfaces()),
                ...array_keys($activeRecord->getTraits(true)),
            ];
        }

        return $this->frameworkClassNames = array_fill_keys($classNames, true);
    }

    /**
     * Returns the readable type of a tag, with template types resolved against its declaring class.
     *
     * @param string $propertyName Name of the tagged property.
     * @param ClassReflection $declaringClass Class, trait, or interface that declares the tag.
     * @param Type|null $readableType Unresolved readable type of the tag, or `null` when the tag is write-only.
     *
     * @return Type|null Readable property type, or `null` when the tag is write-only.
     */
    private function resolveReadableType(
        string $propertyName,
        ClassReflection $declaringClass,
        Type|null $readableType,
    ): Type|null {
        // the declaring class holds the tag itself, so PHPStan resolves this exact tag against its template type map
        if ($readableType === null || $this->annotationsProperties->hasProperty($declaringClass, $propertyName) === false) {
            return $readableType;
        }

        return $this->annotationsProperties->getProperty($declaringClass, $propertyName)->getReadableType();
    }
}
