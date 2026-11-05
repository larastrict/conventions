<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class ArchitectureProfileTest extends TestCase
{
    public function testDefaultRulesCanBeDisabledByNamedService(): void
    {
        $root = dirname(__DIR__, 2);
        $directory = sys_get_temp_dir() . '/larastrict-defaults-' . bin2hex(random_bytes(8));
        mkdir($directory . '/Actions', recursive: true);
        $fixture = $directory . '/Actions/NoExecuteAction.php';
        file_put_contents($fixture, '<?php namespace App\\Actions; final class NoExecuteAction {}');

        try {
            foreach ([true, false] as $enabled) {
                $configuration = "includes:\n    - " . $root . "/extension.neon\nparameters:\n    level: max\n    tmpDir: " . $directory . "/cache\n    bootstrapFiles:\n        - " . $fixture . "\n";

                if ($enabled === false) {
                    $configuration .= "services:\n    larastrict.actionQueryPublicApi:\n        arguments:\n            enabled: false\n";
                }

                file_put_contents($directory . '/phpstan.neon', $configuration);
                $process = new Process([PHP_BINARY, $root . '/vendor/bin/phpstan', 'analyse', '-c', $directory . '/phpstan.neon', '--no-progress', '--error-format=json', $fixture], $root);
                $process->setTimeout(60);
                $process->run();
                $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
                self::assertSame($enabled ? 1 : 0, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
                self::assertIsArray($result);
                $totals = $result['totals'];
                self::assertIsArray($totals);
                self::assertSame($enabled ? 1 : 0, $totals['file_errors'], $process->getOutput());
            }
        } finally {
            (new Filesystem())->deleteDirectory($directory);
        }
    }

    public function testDefaultContainerRuleResolvesWiredClassesAndReportsMissingConstructorInputs(): void
    {
        $root = dirname(__DIR__, 2);
        $directory = sys_get_temp_dir() . '/larastrict-injectable-' . bin2hex(random_bytes(8));
        mkdir($directory . '/Actions', recursive: true);

        try {
            file_put_contents($directory . '/Actions/ReadyAction.php', '<?php namespace App\\Actions; final class ReadyAction { public function execute(): void {} }');
            file_put_contents($directory . '/Actions/UnreadyAction.php', '<?php namespace App\\Actions; final class UnreadyAction { public function __construct(public readonly string $token) {} public function execute(): void {} }');
            file_put_contents($directory . '/bootstrap.php', '<?php $loader = new \\Composer\\Autoload\\ClassLoader(); $loader->addPsr4("App\\\\Actions\\\\", __DIR__ . "/Actions"); $loader->register(true);');
            file_put_contents($directory . '/phpstan.neon', "includes:\n    - " . $root . "/phpstan.neon\nparameters:\n    reportUnmatchedIgnoredErrors: false\n    excludePaths!:\n        analyse: []\n    tmpDir: " . $directory . "/cache\n    bootstrapFiles:\n        - " . $directory . "/bootstrap.php\n");
            $process = new Process([PHP_BINARY, $root . '/vendor/bin/phpstan', 'analyse', '-c', $directory . '/phpstan.neon', '--no-progress', '--error-format=json', '--memory-limit=1G', $directory . '/Actions/ReadyAction.php', $directory . '/Actions/UnreadyAction.php'], $root);
            $process->setTimeout(60);
            $process->run();
            $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
            self::assertSame(1, $process->getExitCode(), $process->getErrorOutput());
            self::assertIsArray($result);
            self::assertSame([
                'errors' => 0,
                'file_errors' => 1,
            ], $result['totals'], $process->getOutput());
            $files = $result['files'];
            self::assertIsArray($files);
            self::assertArrayNotHasKey($directory . '/Actions/ReadyAction.php', $files);
            $file = $files[$directory . '/Actions/UnreadyAction.php'];
            self::assertIsArray($file);
            $messages = $file['messages'];
            self::assertIsArray($messages);
            $message = $messages[0];
            self::assertIsArray($message);
            self::assertSame('larstrict.usableInContainer', $message['identifier']);
        } finally {
            (new Filesystem())->deleteDirectory($directory);
        }
    }

    public function testParameterNamingOnlyChecksConfiguredInjectableDeclaringClasses(): void
    {
        $root = dirname(__DIR__, 2);
        $directory = sys_get_temp_dir() . '/larastrict-parameter-names-' . bin2hex(random_bytes(8));
        mkdir($directory . '/Pilot/Services', recursive: true);
        $service = $directory . '/Pilot/Services/PaymentService.php';
        $fixture = $directory . '/Pilot/Services/ConsumerService.php';
        $ordinary = $directory . '/Pilot/Consumer.php';
        file_put_contents($service, '<?php namespace App\\Pilot\\Services; final class PaymentService {}');
        file_put_contents($fixture, '<?php namespace App\\Pilot\\Services; final class ConsumerService { public function __construct(public readonly PaymentService $gateway) {} }');
        file_put_contents($ordinary, '<?php namespace App\\Pilot; final class Consumer { public function __construct(public readonly Services\\PaymentService $gateway) {} }');

        try {
            foreach ([false, true] as $scopeDisabled) {
                $configuration = "includes:\n    - " . $root . "/phpstan.neon\nparameters:\n    tmpDir: " . $directory . "/cache\n    bootstrapFiles:\n        - " . $service . "\n        - " . $fixture . "\n        - " . $ordinary . "\n";

                if ($scopeDisabled) {
                    $configuration .= "services:\n    larastrict.parameterName:\n        arguments:\n            injectableNamespaces: []\n            injectableBaseClasses: []\n";
                }

                file_put_contents($directory . '/phpstan.neon', $configuration);
                $process = new Process([PHP_BINARY, $root . '/vendor/bin/phpstan', 'analyse', '-c', $directory . '/phpstan.neon', '--no-progress', '--error-format=json', $fixture, $ordinary], $root);
                $process->setTimeout(60);
                $process->run();
                $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
                self::assertSame($scopeDisabled ? 0 : 1, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
                self::assertIsArray($result);
                $totals = $result['totals'];
                self::assertIsArray($totals);
                self::assertSame($scopeDisabled ? 0 : 1, $totals['file_errors'], $process->getOutput());
            }
        } finally {
            (new Filesystem())->deleteDirectory($directory);
        }
    }

    public function testCompleteStaticProfileAllowsEveryQueryResultKind(): void
    {
        $root = dirname(__DIR__, 2);
        $directory = sys_get_temp_dir() . '/larastrict-profile-' . bin2hex(random_bytes(8));
        mkdir($directory);

        try {
            $namespace = json_encode('LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Profile', JSON_THROW_ON_ERROR);
            $configuration = "includes:\n    - " . $root . "/phpstan.neon\nparameters:\n    reportUnmatchedIgnoredErrors: false\n    excludePaths!:\n        analyse: []\n    tmpDir: " . $directory . "/cache\nservices:\n";
            foreach (['classPlacement', 'parameterName', 'actionQueryPublicApi', 'dependencies', 'viewData', 'modelIdentifiers', 'requestKeys'] as $service) {
                $configuration .= '    larastrict.' . $service . ":\n        arguments:\n            applicationNamespaces: [" . $namespace . "]\n";
            }

            file_put_contents($directory . '/phpstan.neon', $configuration);
            $process = new Process([PHP_BINARY, $root . '/vendor/bin/phpstan', 'analyse', '-c', $directory . '/phpstan.neon', '--no-progress', '--memory-limit=1G', __DIR__ . '/Fixtures/Profile'], $root);
            $process->setTimeout(60);
            $process->run();
            self::assertSame(0, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        } finally {
            (new Filesystem())->deleteDirectory($directory);
        }
    }

    public function testInjectionRegistryConfigurationIsSharedByNamingAndResolutionRules(): void
    {
        $root = dirname(__DIR__, 2);
        $directory = sys_get_temp_dir() . '/larastrict-injection-points-' . bin2hex(random_bytes(8));
        mkdir($directory . '/Portal/Services', recursive: true);
        $fixture = $directory . '/Portal/Consumer.php';
        $service = $directory . '/Portal/Services/ReadyService.php';
        $base = $directory . '/Portal/Handler.php';
        file_put_contents($service, '<?php namespace App\\Portal\\Services; final class ReadyService {}');
        file_put_contents($base, '<?php namespace App\\Portal; abstract class Handler {}');
        file_put_contents($fixture, '<?php namespace App\\Portal; final class Consumer extends Handler { public function process(Services\\ReadyService $value): void { app(Services\\ReadyService::class); } }');

        try {
            foreach ([false, true] as $registered) {
                $configuration = "includes:\n    - " . $root . "/phpstan.neon\nparameters:\n    tmpDir: " . $directory . "/cache\n    bootstrapFiles:\n        - " . $service . "\n        - " . $base . "\n        - " . $fixture . "\n";

                if ($registered) {
                    $configuration .= "services:\n    larastrict.injectionPoints:\n        arguments:\n            additionalMethodInjection:\n                App\\Portal\\Handler: [process]\n";
                }

                file_put_contents($directory . '/phpstan.neon', $configuration);
                $process = new Process([PHP_BINARY, $root . '/vendor/bin/phpstan', 'analyse', '-c', $directory . '/phpstan.neon', '--no-progress', '--error-format=json', $fixture], $root);
                $process->setTimeout(60);
                $process->run();
                $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
                self::assertSame($registered ? 1 : 0, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
                self::assertIsArray($result);
                $totals = $result['totals'];
                self::assertIsArray($totals);
                self::assertSame($registered ? 2 : 0, $totals['file_errors'], $process->getOutput());

                if ($registered) {
                    self::assertStringContainsString('larastrict.dependencies.containerResolution', $process->getOutput());
                    self::assertStringContainsString('larastrict.naming.parameterName', $process->getOutput());
                }
            }
        } finally {
            (new Filesystem())->deleteDirectory($directory);
        }
    }
}
