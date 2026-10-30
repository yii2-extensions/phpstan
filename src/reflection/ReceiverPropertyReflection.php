<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\reflection;

use PHPStan\Reflection\{ClassReflection, PropertyReflection};
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

/**
 * Wraps an inherited {@see PropertyReflection} so that it's declared by the class it was resolved on.
 *
 * PHPStan resolves a property again on the ancestor that declares it. An application class unrelated to the configured
 * application type inherits annotations from {@see \yii\base\Application}, which is refined to the configured type, so
 * declaring the property on the receiver keeps the configured components from replacing its own annotated type.
 * Delegates every other aspect of the property to the wrapped instance.
 *
 * {@see PropertyReflection} for property reflection contract.
 */
final class ReceiverPropertyReflection implements PropertyReflection
{
    /**
     * Creates a new instance of the {@see ReceiverPropertyReflection} class.
     *
     * @param PropertyReflection $property Inherited property reflection instance for delegation.
     * @param ClassReflection $declaringClass Class reflection of the receiver the property was resolved on.
     */
    public function __construct(
        private readonly PropertyReflection $property,
        private readonly ClassReflection $declaringClass,
    ) {}

    /**
     * Determines whether the type of the property can change after assignment.
     *
     * @return bool `true` if the property type can change after assignment; `false` otherwise.
     */
    public function canChangeTypeAfterAssignment(): bool
    {
        return $this->property->canChangeTypeAfterAssignment();
    }

    /**
     * Retrieves the receiver class reflection the property was resolved on.
     *
     * @return ClassReflection Declaring class reflection instance for the property.
     */
    public function getDeclaringClass(): ClassReflection
    {
        return $this->declaringClass;
    }

    /**
     * Retrieves the deprecation description of the property, if available.
     *
     * @return string|null Deprecation description if available, or `null` if not deprecated or no description is set.
     */
    public function getDeprecatedDescription(): string|null
    {
        return $this->property->getDeprecatedDescription();
    }

    /**
     * Retrieves the PHPDoc comment of the property, if available.
     *
     * @return string|null PHPDoc comment string for the property, or `null` if no comment is set.
     */
    public function getDocComment(): string|null
    {
        return $this->property->getDocComment();
    }

    /**
     * Retrieves the type that can be read from the property.
     *
     * @return Type Type that can be read from the property for static analysis.
     */
    public function getReadableType(): Type
    {
        return $this->property->getReadableType();
    }

    /**
     * Retrieves the type that can be written to the property.
     *
     * @return Type Type that can be written to the property for static analysis.
     */
    public function getWritableType(): Type
    {
        return $this->property->getWritableType();
    }

    /**
     * Determines whether the property is deprecated.
     *
     * @return TrinaryLogic Deprecation status of the property as a {@see TrinaryLogic} value.
     */
    public function isDeprecated(): TrinaryLogic
    {
        return $this->property->isDeprecated();
    }

    /**
     * Determines whether the property is internal to the library or framework.
     *
     * @return TrinaryLogic Internal status of the property as a {@see TrinaryLogic} value.
     */
    public function isInternal(): TrinaryLogic
    {
        return $this->property->isInternal();
    }

    /**
     * Determines whether the property is private.
     *
     * @return bool `true` if the property is private; `false` otherwise.
     */
    public function isPrivate(): bool
    {
        return $this->property->isPrivate();
    }

    /**
     * Determines whether the property is public.
     *
     * @return bool `true` if the property is public; `false` otherwise.
     */
    public function isPublic(): bool
    {
        return $this->property->isPublic();
    }

    /**
     * Determines whether the property is readable.
     *
     * @return bool `true` if the property is readable; `false` otherwise.
     */
    public function isReadable(): bool
    {
        return $this->property->isReadable();
    }

    /**
     * Determines whether the property is static.
     *
     * @return bool `true` if the property is static; `false` otherwise.
     */
    public function isStatic(): bool
    {
        return $this->property->isStatic();
    }

    /**
     * Determines whether the property is writable.
     *
     * @return bool `true` if the property is writable; `false` otherwise.
     */
    public function isWritable(): bool
    {
        return $this->property->isWritable();
    }
}
