<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Throwable;
use TypeError;
use Yii;
use yii\base\Model;
use yii\di\Container;
use yii\validators\{RequiredValidator, Validator};
use yii2\extensions\phpstan\rules\ValidatorInvalidDefinitionRule;
use yii2\extensions\phpstan\tests\data\rule\validator\RuleProbeModel;
use yii2\extensions\phpstan\tests\provider\{
    ValidatorInvalidDefinitionRuleProvider,
    ValidatorInvalidDefinitionRuntimeProvider,
};

use function array_diff;
use function array_intersect_key;
use function array_keys;
use function count;
use function sprintf;

/**
 * Runtime tests for the validator creation outcomes of the {@see ValidatorInvalidDefinitionRule} fixtures on the
 * installed Yii.
 *
 * {@see ValidatorInvalidDefinitionRuntimeProvider} for test case data providers.
 */
final class ValidatorInvalidDefinitionRuntimeTest extends TestCase
{
    /**
     * Container in place before the test, restored afterwards.
     */
    private Container|null $previousContainer = null;

    /**
     * @phpstan-param class-string<Model> $model
     */
    #[DataProviderExternal(ValidatorInvalidDefinitionRuntimeProvider::class, 'ruleProvider')]
    public function testCreateFixtureRuleFailingExactlyWhenReported(string $model, int|string $index, bool $reported): void
    {
        $rule = array_intersect_key((new $model())->rules(), [$index => true]);

        self::assertCount(
            1,
            $rule,
            'Fixture must return the recorded rule.',
        );

        $actual = null;

        try {
            (new RuleProbeModel($rule))->getValidators();
        } catch (Throwable $e) {
            // the message of the `TypeError` differs between PHP versions; its class and origin don't
            $actual = [$e::class, $e->getFile(), $e->getTrace()[0]['function'] ?? null];
        }

        self::assertSame(
            $reported
                ? [TypeError::class, (new ReflectionClass(Validator::class))->getFileName(), 'createValidator']
                : null,
            $actual,
            sprintf('Creation outcome of rule `%s` must match the diagnostic.', $index),
        );
    }

    /**
     * @phpstan-param class-string<Model> $model
     */
    #[DataProviderExternal(ValidatorInvalidDefinitionRuntimeProvider::class, 'overrideProvider')]
    public function testCreateNoValidatorWhenOverrideIgnoresInvalidType(string $model): void
    {
        $instance = new $model();

        self::assertSame(
            [['name', ['class' => RequiredValidator::class]]],
            $instance->rules(),
            'Fixture must return the invalid validator type.',
        );
        self::assertCount(
            0,
            $instance->getValidators(),
            'Override must not consume the rules.',
        );
    }

    /**
     * @phpstan-param class-string<Model> $model
     */
    #[DataProviderExternal(ValidatorInvalidDefinitionRuntimeProvider::class, 'validProvider')]
    public function testCreateOneValidatorPerRuleOfValidFixture(string $model): void
    {
        $instance = new $model();

        self::assertCount(
            count($instance->rules()),
            $instance->getValidators(),
            'Every rule must create one validator.',
        );
    }

    public function testRecordOnlyRulesThatEachFixtureReturns(): void
    {
        foreach (ValidatorInvalidDefinitionRuleProvider::reportedRules() as $model => $reported) {
            self::assertSame(
                [],
                array_diff($reported, array_keys((new $model())->rules())),
                sprintf('Recorded rules of `%s` must exist.', $model),
            );
        }
    }

    protected function setUp(): void
    {
        $this->previousContainer = Yii::$container;

        Yii::$container = new Container();
    }

    protected function tearDown(): void
    {
        if ($this->previousContainer !== null) {
            Yii::$container = $this->previousContainer;
        }

        $this->previousContainer = null;
    }
}
