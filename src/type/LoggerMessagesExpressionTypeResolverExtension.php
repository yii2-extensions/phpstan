<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\{
    ArrayType,
    ClassStringType,
    ExpressionTypeResolverExtension,
    FloatType,
    IntegerType,
    MixedType,
    ObjectType,
    StringType,
    Type,
    TypeCombinator,
};
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Constant\{ConstantArrayType, ConstantArrayTypeBuilder, ConstantIntegerType, ConstantStringType};
use Throwable;
use yii\log\{Logger, PsrMessage, Target};

/**
 * Infers the documented tuple shape for Yii logger and log-target message buffers.
 *
 * Yii exposes both buffers as native properties documented only as `array`. This expression resolver recognizes reads
 * of `Logger::$messages` and `Target::$messages` and constructs their precise PHPStan array types directly.
 *
 * {@see ExpressionTypeResolverExtension} for PHPStan expression type resolver contract.
 * {@see Logger} for the logger message structure.
 * {@see Target} for filtered target message buffers.
 */
final class LoggerMessagesExpressionTypeResolverExtension implements ExpressionTypeResolverExtension
{
    /**
     * @param ReflectionProvider $reflectionProvider Reflection provider used to detect Yii 22.0 PSR message support.
     */
    public function __construct(private readonly ReflectionProvider $reflectionProvider) {}

    /**
     * Resolves reads of Yii logger message-buffer properties to their precise array shapes.
     *
     * @param Expr $expr Expression being analyzed.
     * @param Scope $scope Current PHPStan analysis scope.
     *
     * @return Type|null Inferred message-buffer type, or `null` for unrelated expressions.
     */
    public function getType(Expr $expr, Scope $scope): Type|null
    {
        if (
            !$expr instanceof PropertyFetch
            || !$expr->name instanceof Identifier
            || $expr->name->toString() !== 'messages'
        ) {
            return null;
        }

        $ownerType = $scope->getType($expr->var);
        $messageType = $this->createMessageType();

        if ((new ObjectType(Logger::class))->isSuperTypeOf($ownerType)->yes()) {
            return new ArrayType(
                TypeCombinator::union(new IntegerType(), new StringType()),
                $messageType,
            );
        }

        if ((new ObjectType(Target::class))->isSuperTypeOf($ownerType)->yes()) {
            return $this->createListType($messageType);
        }

        return null;
    }

    /**
     * Creates a PHPStan list type for the provided item type.
     */
    private function createListType(Type $itemType): Type
    {
        return TypeCombinator::intersect(
            new ArrayType(new IntegerType(), $itemType),
            new AccessoryArrayListType(),
        );
    }

    /**
     * Creates the tuple type emitted by Yii's logger, a list whose sixth element (memory usage) is optional.
     */
    private function createMessageType(): Type
    {
        $payloadTypes = [
            new StringType(),
            new ArrayType(new MixedType(), new MixedType(true)),
            new ObjectType(Throwable::class),
        ];

        if ($this->reflectionProvider->hasClass(PsrMessage::class)) {
            $payloadTypes[] = new ObjectType(PsrMessage::class);
        }

        $elementTypes = [
            TypeCombinator::union(...$payloadTypes),
            new IntegerType(),
            new StringType(),
            new FloatType(),
            $this->createListType($this->createTraceFrameType()),
            new IntegerType(),
        ];

        $messageTypeBuilder = ConstantArrayTypeBuilder::createEmpty();

        foreach ($elementTypes as $index => $elementType) {
            $messageTypeBuilder->setOffsetValueType(new ConstantIntegerType($index), $elementType, $index === 5);
        }

        return $messageTypeBuilder->getArray();
    }

    /**
     * Creates the trace-frame shape retained by Yii after collecting a backtrace.
     */
    private function createTraceFrameType(): ConstantArrayType
    {
        return new ConstantArrayType(
            [
                new ConstantStringType('file'),
                new ConstantStringType('line'),
                new ConstantStringType('function'),
                new ConstantStringType('class'),
                new ConstantStringType('type'),
            ],
            [
                new StringType(),
                new IntegerType(),
                new StringType(),
                new ClassStringType(),
                new StringType(),
            ],
            [0],
            [2, 3, 4],
        );
    }
}
