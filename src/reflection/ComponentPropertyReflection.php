<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\reflection;

use PHPStan\Reflection\{ClassReflection, PropertyReflection};
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

/**
 * Property reflection wrapper for Yii application components in PHPStan analysis.
 *
 * Provides a property reflection implementation for dynamic Yii application components resolved via the service map,
 * enabling accurate type inference and static analysis for properties injected or registered at runtime.
 *
 * This class delegates most property reflection behavior to a fallback {@see PropertyReflection} instance, while
 * overriding the type to reflect the actual component type as determined by the service map or dependency injection.
 *
 * The wrapper ensures that PHPStan can correctly infer the type, visibility, and other property characteristics for
 * dynamic application components, supporting IDE autocompletion, and strict static analysis.
 *
 * Key features.
 * - Accurate type reflection for dynamic Yii application components.
 * - Delegates property behavior to a fallback property reflection instance.
 * - Ensures compatibility with PHPStan's strict analysis and autocompletion.
 * - Integrates with service-map-based component resolution.
 * - Supports all property visibility and mutability checks.
 *
 * @see PropertyReflection for property reflection contract.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ComponentPropertyReflection implements PropertyReflection
{
    /**
     * Creates a new instance of the {@see ComponentPropertyReflection} class.
     *
     * @param PropertyReflection $fallbackProperty Fallback property reflection instance for delegation.
     * @param Type $type Actual type of the dynamic component as resolved by the service map.
     */
    public function __construct(private readonly PropertyReflection $fallbackProperty, private readonly Type $type) {}

    /**
     * Determines whether the type of the dynamic Yii application component property can change after assignment.
     *
     * Delegates the mutability checks to the fallback {@see PropertyReflection} instance, ensuring that type change
     * semantics are preserved according to the original property definition.
     *
     * This method allows static analysis tools and IDEs to correctly identify whether the property type can change
     * after assignment for dynamic application components, supporting accurate type checking and code analysis.
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
     * This method ensures that static analysis tools and IDEs can accurately trace the origin of the property,
     * supporting correct type inference and property resolution for dynamic application components.
     *
     * @return ClassReflection Declaring class reflection instance for the property.
     */
    public function getDeclaringClass(): ClassReflection
    {
        return $this->fallbackProperty->getDeclaringClass();
    }

    /**
     * Retrieves the deprecation description for the dynamic Yii application component property, if available.
     *
     * Delegates the retrieval of the deprecation description to the fallback {@see PropertyReflection} instance,
     * ensuring that any deprecation metadata or rationale is preserved according to the original property definition.
     *
     * This method allows static analysis tools and IDEs to display detailed deprecation messages for dynamic
     * application components, supporting accurate code completion, type checking, and developer guidance.
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
     * This method allows static analysis tools and IDEs to display inline documentation for dynamic application
     * components, supporting accurate code completion, type checking, and developer guidance.
     *
     * @return string|null PHPDoc comment string if available, or `null` if no documentation is set.
     */
    public function getDocComment(): string|null
    {
        return $this->fallbackProperty->getDocComment();
    }

    /**
     * Retrieves the readable type for the dynamic Yii application component property.
     *
     * Delegates the readable type resolution to the fallback {@see PropertyReflection} instance ensuring that the type
     * exposed for reading is consistent with the original property definition.
     *
     * This method allows static analysis tools and IDEs to provide accurate type inference and code completion for
     * dynamic application components when accessed as readable properties.
     *
     * @return Type Type that can be read from the property for static analysis.
     */
    public function getReadableType(): Type
    {
        return $this->fallbackProperty->getReadableType();
    }

    /**
     * Retrieves the static analysis type for the dynamic Yii application component property.
     *
     * Returns the type as resolved by the service map or dependency injection, enabling accurate type inference for
     * dynamic properties injected or registered at runtime.
     *
     * This method ensures that PHPStan and IDEs can provide correct autocompletion and type checking for application
     * components accessed as properties.
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
     * This method allows static analysis tools and IDEs to provide accurate type inference and code completion for
     * dynamic application components when accessed as writable properties.
     *
     * @return Type Type that can be written to the property for static analysis.
     */
    public function getWritableType(): Type
    {
        return $this->fallbackProperty->getWritableType();
    }

    /**
     * Determines whether the dynamic Yii application component property is deprecated.
     *
     * Delegates the deprecation status check to the fallback {@see PropertyReflection} instance, ensuring that
     * deprecation metadata is preserved according to the original property definition.
     *
     * This method allows static analysis tools and IDEs to correctly identify deprecated properties for dynamic
     * application components, supporting accurate code completion, type checking, and deprecation warnings.
     *
     * @return TrinaryLogic Deprecation status of the property as a {@see TrinaryLogic} value.
     */
    public function isDeprecated(): TrinaryLogic
    {
        return $this->fallbackProperty->isDeprecated();
    }

    /**
     * Determines whether the dynamic Yii application component property is internal to the library or framework.
     *
     * Delegates the internal status check to the fallback {@see PropertyReflection} instance, ensuring that internal
     * metadata is preserved according to the original property definition.
     *
     * This method allows static analysis tools and IDEs to correctly identify internal properties for dynamic
     * application components, supporting accurate code completion, type checking, and visibility enforcement.
     *
     * @return TrinaryLogic Internal status of the property as a {@see TrinaryLogic} value.
     */
    public function isInternal(): TrinaryLogic
    {
        return $this->fallbackProperty->isInternal();
    }

    /**
     * Determines whether the dynamic Yii application component property is private.
     *
     * Delegates the privacy check to the fallback {@see PropertyReflection} instance ensuring that property visibility
     * is preserved according to the original property definition.
     *
     * This method allows static analysis tools and IDEs to correctly identify private properties for dynamic
     * application components, supporting accurate code completion and type checking.
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
     * This method allows static analysis tools and IDEs to correctly identify public properties for dynamic
     * application components, supporting accurate code completion and type checking.
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
     * This method allows static analysis tools and IDEs to correctly identify static properties for dynamic application
     * components, supporting accurate code completion and type checking.
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
