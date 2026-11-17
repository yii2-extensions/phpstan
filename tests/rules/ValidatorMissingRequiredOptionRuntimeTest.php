<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use Closure;
use PHPUnit\Framework\TestCase;
use Throwable;
use Yii;
use yii\base\{InvalidCallException, InvalidConfigException, Model, UnknownPropertyException};
use yii\di\Container;
use yii\validators\Validator;
use yii2\extensions\phpstan\tests\data\rule\validator\required\{
    MethodOverrideModel,
    ReportedModel,
    UncertainModel,
    ValidModel,
};

use function array_slice;
use function count;
use function dirname;
use function is_array;
use function is_string;
use function sprintf;

/**
 * Runtime tests for the rules reported by {@see \yii2\extensions\phpstan\rules\ValidatorMissingRequiredOptionRule}.
 */
final class ValidatorMissingRequiredOptionRuntimeTest extends TestCase
{
    /**
     * Container restored after each isolated runtime test.
     */
    private Container|null $previousContainer = null;

    public function testConstructEveryValidControl(): void
    {
        $model = new ValidModel();

        self::assertCount(
            15,
            $model->getValidators(),
            'Every valid rule must construct a validator.',
        );
    }

    public function testRecordTheOutcomeOfEveryStandAsideRule(): void
    {
        $outcomes = [
            InvalidConfigException::class,
            InvalidConfigException::class,
            null,
            UnknownPropertyException::class,
            InvalidCallException::class,
            InvalidConfigException::class,
            null,
            null,
            InvalidConfigException::class,
            InvalidConfigException::class,
        ];

        self::assertSame(
            $outcomes,
            self::outcomes(new UncertainModel()),
            'Each stand-aside rule must keep its recorded runtime outcome.',
        );
        self::assertSame(
            [null, InvalidConfigException::class],
            self::outcomes(new MethodOverrideModel()),
            'Only the alias must bypass the overridden method lookup.',
        );
    }

    public function testRejectEveryReportedRuleWithItsRequiredOption(): void
    {
        $model = new ReportedModel();

        $options = [
            'range',
            'range',
            'range',
            'pattern',
            'pattern',
            'pattern',
            'filter',
            'filter',
            'filter',
            'range',
            'range',
            'range',
        ];

        self::assertCount(
            count($options),
            $model->rules(),
            'Each fixture rule must have a recorded required option.',
        );

        foreach ($model->rules() as $index => $rule) {
            try {
                self::create($model, $rule);
                self::fail(
                    'A missing required option must reject construction.',
                );
            } catch (InvalidConfigException $exception) {
                self::assertSame(
                    sprintf('The "%s" property must be set.', $options[$index] ?? ''),
                    $exception->getMessage(),
                    "Rule #{$index} must reject its required option.",
                );
            }
        }
    }

    protected function setUp(): void
    {
        $config = require dirname(__DIR__) . '/config/validator-required-config.php';

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
