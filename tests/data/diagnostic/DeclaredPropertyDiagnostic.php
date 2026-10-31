<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\diagnostic;

use Yii;
use yii\console\Application as ConsoleApplication;
use yii\web\Application as WebApplication;

/**
 * Shared code fixture for the native diagnostics that PHPStan reports on the stub-declared `Yii::$app` and
 * `Module::$params` types, analyzed once with a Web and once with a console application.
 */
final class DeclaredPropertyDiagnostic
{
    public function applicationOrFallback(WebApplication $fallback): object
    {
        return Yii::$app ?? $fallback;
    }

    public function hasApplication(): bool
    {
        return isset(Yii::$app);
    }

    public function isApplicationMissing(): bool
    {
        return Yii::$app === null;
    }

    public function isConsoleApplication(): bool
    {
        return (bool) (Yii::$app instanceof ConsoleApplication)



         ;
    }

    public function isWebApplication(): bool
    {
        return (bool) (Yii::$app instanceof WebApplication)



         ;
    }

    public function missingParam(): string
    {
        return Yii::$app->params['missing'] ?? 'default';
    }

    public function resetApplication(): void
    {
        Yii::$app = null;
    }
}
