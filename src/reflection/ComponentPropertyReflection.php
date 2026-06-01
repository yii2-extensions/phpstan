<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\reflection;

use PHPStan\Reflection\{ClassReflection, PropertyReflection};
use PHPStan\TrinaryLogic;
use PHPStan\Type\{Type, VerbosityLevel};

use function sprintf;

/**
 * Wraps a fallback {@see PropertyReflection} to expose the resolved component type for dynamic Yii application
 * components in PHPStan analysis.
 *
 * Delegates visibility and mutability checks to the fallback instance while overriding the type to reflect the actual
 * component type as determined by the service map or dependency injection.
 *
 * {@see PropertyReflection} for property reflection contract.
 */
final class ComponentPropertyReflection implements PropertyReflection
{
    /**
     * Creates a new instance of the {@see ComponentPropertyReflection} class.
     *
     * @param PropertyReflection $fallbackProperty Fallback property reflection instance for delegation.
     * @param Type $type Type of the dynamic component as resolved by the service map or dependency injection.
     * @param ClassReflection $declaringClass Class reflection of the class declaring the dynamic property.
     */
    public function __construct(
        private readonly PropertyReflection $fallbackProperty,
        private readonly Type $type,
        private readonly ClassReflection $declaringClass,
    ) {}

    /**
     * Determines whether the type of the dynamic Yii application component property can change after assignment.
     *
     * Delegates the mutability checks to the fallback {@see PropertyReflection} instance, ensuring that type change
     * semantics are preserved according to the original property definition.
     *
     * @return bool `true` if the property type can change after assignment; `false` otherwise.
     */
    public function canChangeTypeAfterAssignment(): bool
    {
        return $this->fallbackProperty->canChangeTypeAfterAssignment();
    }

    /**
     * Retrieves the declaring class reflection for the dynamic Yii application component property.
     *
     * Delegates to the fallback {@see PropertyReflection} instance to determine the class in which the property is
     * declared.
     *
     * @return ClassReflection Declaring class reflection instance for the property.
     */
    public function getDeclaringClass(): ClassReflection
    {
        return $this->declaringClass;
    }

    /**
     * Retrieves the deprecation description for the dynamic Yii application component property, if available.
     *
     * Delegates the retrieval of the deprecation description to the fallback {@see PropertyReflection} instance,
     * ensuring that any deprecation metadata or rationale is preserved according to the original property definition.
     *
     * @return string|null Deprecation description if available, or `null` if not deprecated or no description is set.
     */
    public function getDeprecatedDescription(): string|null
    {
        return $this->fallbackProperty->getDeprecatedDescription();
    }

    /**
     * Retrieves the PHPDoc comment for the dynamic Yii application component property, if available.
     *
     * Delegates the retrieval of the PHPDoc comment to the fallback {@see PropertyReflection} instance, ensuring that
     * documentation metadata is preserved according to the original property definition.
     *
     * @return string PHPDoc comment string for the property, or an empty string if no comment is set.
     */
    public function getDocComment(): string
    {
        $componentTypeName = $this->type->describe(VerbosityLevel::typeOnly());

        return sprintf("/**\n * @var %s\n */", $componentTypeName);
    }

    /**
     * Retrieves the readable type for the dynamic Yii application component property.
     *
     * Delegates the readable type resolution to the fallback {@see PropertyReflection} instance ensuring that the type
     * exposed for reading is consistent with the original property definition.
     *
     * @return Type Type that can be read from the property for static analysis.
     */
    public function getReadableType(): Type
    {
        return $this->type;
    }

    /**
     * Retrieves the static analysis type for the dynamic Yii application component property.
     *
     * Returns the type as resolved by the service map or dependency injection, enabling accurate type inference for
     * dynamic properties injected or registered at runtime.
     *
     * @return Type Actual type of the dynamic component property for static analysis.
     */
    public function getType(): Type
    {
        return $this->type;
    }

    /**
     * Retrieves the writable type for the dynamic Yii application component property.
     *
     * Delegates the writable type resolution to the fallback {@see PropertyReflection} instance ensuring that the type
     * exposed for writing is consistent with the original property definition.
     *
     * @return Type Type that can be written to the property for static analysis.
     */
    public function getWritableType(): Type
    {
        return $this->type;
    }

    /**
     * Determines whether the dynamic Yii application component property is deprecated.
     *
     * Delegates the deprecation status check to the fallback {@see PropertyReflection} instance, ensuring that
     * deprecation metadata is preserved according to the original property definition.
     *
     * @return TrinaryLogic Deprecation status of the property as a {@see TrinaryLogic} value.
     */
    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    /**
     * Determines whether the dynamic Yii application component property is internal to the library or framework.
     *
     * Delegates the internal status check to the fallback {@see PropertyReflection} instance, ensuring that internal
     * metadata is preserved according to the original property definition.
     *
     * @return TrinaryLogic Internal status of the property as a {@see TrinaryLogic} value.
     */
    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    /**
     * Determines whether the dynamic Yii application component property is private.
     *
     * Delegates the privacy check to the fallback {@see PropertyReflection} instance ensuring that property visibility
     * is preserved according to the original property definition.
     *
     * @return bool `true` if the property is private; `false` otherwise.
     */
    public function isPrivate(): bool
    {
        return $this->fallbackProperty->isPrivate();
    }

    /**
     * Determines whether the dynamic Yii application component property is public.
     *
     * Delegates the public visibility check to the fallback {@see PropertyReflection} instance, ensuring that property
     * visibility is preserved according to the original property definition.
     *
     * @return bool `true` if the property is public; `false` otherwise.
     */
    public function isPublic(): bool
    {
        return $this->fallbackProperty->isPublic();
    }

    /**
     * Determines whether the dynamic Yii application component property is readable.
     *
     * Delegates the readability checks to the fallback {@see PropertyReflection} instance ensuring that visibility and
     * access rules are respected according to the original property definition.
     *
     * @return bool `true` if the property is readable; `false` otherwise.
     */
    public function isReadable(): bool
    {
        return $this->fallbackProperty->isReadable();
    }

    /**
     * Determines whether the dynamic Yii application component property is static.
     *
     * Delegates the static check to the fallback {@see PropertyReflection} instance, ensuring that static property
     * semantics are preserved according to the original property definition.
     *
     * @return bool `true` if the property is static; `false` otherwise.
     */
    public function isStatic(): bool
    {
        return $this->fallbackProperty->isStatic();
    }

    /**
     * Determines whether the dynamic Yii application component property is writable.
     *
     * Delegates the writability check to the fallback {@see PropertyReflection} instance, ensuring that mutability and
     * access rules are respected according to the original property definition.
     *
     * @return bool `true` if the property is writable; `false` otherwise.
     */
    public function isWritable(): bool
    {
        return $this->fallbackProperty->isWritable();
    }
}
