<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use Closure;
use Error;
use PHPUnit\Framework\TestCase;
use Throwable;
use TypeError;
use Yii;
use yii\base\{Model, UnknownPropertyException};
use yii\di\{Container, NotInstantiableException};
use yii\validators\{TrimValidator, Validator};
use yii2\extensions\phpstan\tests\data\rule\validator\type\{
    AliasDefaultModel,
    ClientScriptModel,
    MethodOverrideModel,
    ReportedModel,
    UncertainModel,
    ValidModel,
};

use function array_slice;
use function dirname;
use function is_array;
use function is_string;
use function property_exists;

/**
 * Runtime tests for the rules reported by {@see \yii2\extensions\phpstan\rules\ValidatorInvalidOptionTypeRule}.
 */
final class ValidatorInvalidOptionTypeRuntimeTest extends TestCase
{
    /**
     * Container restored after each isolated runtime test.
     */
    private Container|null $previousContainer = null;

    public function testConstructEveryValidControl(): void
    {
        self::assertSame(
            [null, null, null, null, null, null, null, null, null, null, null, null, null, null],
            self::outcomes(new ValidModel()),
            'Every valid rule must construct a validator.',
        );
    }

    public function testRecordTheOutcomeOfEveryStandAsideRule(): void
    {
        self::assertSame(
            [
                TypeError::class,
                Error::class,
                NotInstantiableException::class,
                null,
                UnknownPropertyException::class,
                UnknownPropertyException::class,
                null,
                TypeError::class,
                TypeError::class,
                NotInstantiableException::class,
            ],
            self::outcomes(new UncertainModel()),
            'Each stand-aside rule must keep its recorded runtime outcome.',
        );
        self::assertSame(
            [UnknownPropertyException::class, TypeError::class],
            self::outcomes(new MethodOverrideModel()),
            'Only the alias must bypass the overridden method lookup.',
        );
    }

    public function testRejectClientScriptOnlyWhereYiiDeclaresItsNativeType(): void
    {
        $rejection = property_exists(TrimValidator::class, 'clientScript')
            ? TypeError::class
            : UnknownPropertyException::class;

        self::assertSame(
            [$rejection, $rejection, property_exists(TrimValidator::class, 'clientScript') ? null : $rejection],
            self::outcomes(new ClientScriptModel()),
            "Only a declared 'clientScript' must reach its type check.",
        );
    }

    public function testRejectEveryReportedRuleWithATypeError(): void
    {
        self::assertSame(
            [
                TypeError::class,
                TypeError::class,
                TypeError::class,
                TypeError::class,
                TypeError::class,
                TypeError::class,
                TypeError::class,
                TypeError::class,
                TypeError::class,
                UnknownPropertyException::class,
            ],
            self::outcomes(new ReportedModel()),
            'Each reported value must be rejected, after any earlier unknown option.',
        );
    }

    public function testRejectReportedRulesWhileCreatingTheModelValidators(): void
    {
        $this->expectException(TypeError::class);

        (new ReportedModel())->getValidators();
    }

    public function testRejectTheAliasDefaultUnlessTheRuleOverridesIt(): void
    {
        self::assertSame(
            [TypeError::class, TypeError::class, null],
            self::outcomes(new AliasDefaultModel()),
            'Only an accepted written override must construct.',
        );
    }

    protected function setUp(): void
    {
        $config = require dirname(__DIR__) . '/config/validator-type-config.php';

        $definitions = is_array($config) && is_array($config['container'] ?? null)
            ? $config['container']['definitions'] ?? null
            : null;

        self::assertIsArray(
            $definitions,
            'Analysis configuration must hold container definitions.',
        );

        $this->previousContainer = Yii::$container;

        Yii::$container = new Container();

        Yii::$container->setDefinitions($definitions);
    }

    protected function tearDown(): void
    {
        if ($this->previousContainer !== null) {
            Yii::$container = $this->previousContainer;
        }

        $this->previousContainer = null;
    }

    /**
     * Creates the validator of one rule as `Model::createValidators()` does.
     *
     * @param mixed $rule Rule to create.
     */
    private static function create(Model $model, mixed $rule): Validator
    {
        self::assertIsArray(
            $rule,
            'Fixture rules must be arrays.',
        );

        $attributes = (array) ($rule[0] ?? []);
        $type = $rule[1] ?? null;

        self::assertTrue(
            $type instanceof Closure || is_string($type),
            'Fixture rules must have a validator type.',
        );

        return Validator::createValidator($type, $model, $attributes, array_slice($rule, 2));
    }

    /**
     * Returns the exception class each rule of the model throws on creation, or `null` when it constructs.
     *
     * @return list<class-string<Throwable>|null>
     */
    private static function outcomes(Model $model): array
    {
        $outcomes = [];

        foreach ($model->rules() as $rule) {
            try {
                self::create($model, $rule);
                $outcomes[] = null;
            } catch (Throwable $exception) {
                $outcomes[] = $exception::class;
            }
        }

        return $outcomes;
    }
}
