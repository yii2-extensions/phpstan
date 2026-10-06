<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PHPStan\Analyser\{DeclarationDependencyTracker, NameScope};
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDocParser\Ast\Type\{IdentifierTypeNode, TypeNode};
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\{ArrayType, MixedType, ObjectType, Type};
use yii2\extensions\phpstan\{ParamsTypeBuilder, ServiceMap, ServiceMapResultCacheValueExtension};

use function ltrim;

/**
 * Resolves the PHPDoc types of `Yii::$app` and `Module::$params` declared by the stub shipped with this extension.
 *
 * The static stub (`stubs/yii.stub`) declares both properties with the placeholder types {@see APPLICATION_TYPE} and
 * {@see PARAMS_TYPE}. This extension resolves them from the Yii configuration: the application placeholder to the
 * configured application class, and the params placeholder to the array shape built by {@see ParamsTypeBuilder}, or to
 * `array` when no params are configured. Every other type node is left to PHPStan.
 *
 * Since the stub never changes, each resolution records that the class declaring the placeholder depends on the
 * configured application class or params shape. PHPStan propagates that dependency to every file depending on the
 * class or on a descendant, so a configuration change re-analyses the files that fetch, override, or inherit either
 * property.
 *
 * {@see TypeNodeResolverExtension} for PHPStan custom PHPDoc type contract.
 * {@see ServiceMap} for service and component map for Yii Application static analysis.
 * {@see ServiceMapResultCacheValueExtension} for the tracked configuration values.
 */
final class ApplicationTypeNodeResolverExtension implements TypeNodeResolverExtension
{
    /**
     * Placeholder PHPDoc type of `Yii::$app`, resolved to the configured application class.
     */
    public const APPLICATION_TYPE = 'yii2-extensions-phpstan-application';

    /**
     * Placeholder PHPDoc type of `Module::$params`, resolved to the configured params shape.
     */
    public const PARAMS_TYPE = 'yii2-extensions-phpstan-params';

    /**
     * Application type resolved on first use.
     */
    private Type|null $applicationType = null;

    /**
     * Params type resolved on first use, since building it walks every configured param.
     */
    private Type|null $paramsType = null;

    /**
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     * @param ReflectionProvider $reflectionProvider Reflection provider for the class declaring a placeholder.
     * @param DeclarationDependencyTracker $dependencyTracker Records the configuration values each class declaration
     * depends on.
     */
    public function __construct(
        private readonly ServiceMap $serviceMap,
        private readonly ReflectionProvider $reflectionProvider,
        private readonly DeclarationDependencyTracker $dependencyTracker,
    ) {}

    /**
     * Resolves the placeholder types of the shipped stub.
     *
     * @param TypeNode $typeNode PHPDoc type node being resolved.
     * @param NameScope $nameScope Name scope of the PHPDoc holding the type node.
     *
     * @return Type|null Configured application or params type, or `null` for any other type node.
     */
    public function resolve(TypeNode $typeNode, NameScope $nameScope): Type|null
    {
        if ($typeNode instanceof IdentifierTypeNode === false) {
            return null;
        }

        $key = match ($typeNode->name) {
            self::APPLICATION_TYPE => ServiceMapResultCacheValueExtension::APPLICATION_KEY,
            self::PARAMS_TYPE => ServiceMapResultCacheValueExtension::PARAMS_KEY,
            default => null,
        };

        if ($key === null) {
            return null;
        }

        $this->trackDeclaringClass($nameScope, $key);

        return $key === ServiceMapResultCacheValueExtension::APPLICATION_KEY
            ? $this->applicationType ??= new ObjectType(ltrim($this->serviceMap->getApplicationType(), '\\'))
            : $this->paramsType ??= $this->buildParamsType();
    }

    /**
     * Builds the params type, or plain `array` when no params are configured.
     */
    private function buildParamsType(): Type
    {
        $params = $this->serviceMap->getParams();

        return $params === [] ? new ArrayType(new MixedType(), new MixedType()) : ParamsTypeBuilder::build($params);
    }

    /**
     * Records that the class declaring the placeholder depends on the configuration value of `$key`.
     *
     * A placeholder outside a class PHPDoc has no declaring class and records nothing; the shipped stub only uses them
     * in class PHPDoc.
     */
    private function trackDeclaringClass(NameScope $nameScope, string $key): void
    {
        $className = $nameScope->getClassName();

        if ($className === null || $this->reflectionProvider->hasClass($className) === false) {
            return;
        }

        $this->dependencyTracker->trackValueDependency(
            $this->reflectionProvider->getClass($className),
            ServiceMapResultCacheValueExtension::class,
            $key,
        );
    }
}
