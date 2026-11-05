<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Random\Randomizer;
use Symfony\Component\Process\Process;

final class ClassPlacementExtensionTest extends TestCase
{
    public function testPackageConfigurationAddsPlacementWithoutReplacingDefaults(): void
    {
        $root = dirname(__DIR__, 2);
        $directory = sys_get_temp_dir() . '/larastrict-placement-' . bin2hex((new Randomizer())->getBytes(8));
        mkdir($directory);

        try {
            file_put_contents($directory . '/package.neon', "services:\n    -\n        class: LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Extensions\\SdkPlacementExtension\n        tags: [larastrict.classPlacementExtension]\n");

            foreach ([false, true] as $extensionEnabled) {
                $configuration = "includes:\n    - " . $root . "/extension.neon\n";

                if ($extensionEnabled) {
                    $configuration .= "    - package.neon\n";
                }

                $configuration .= "parameters:\n    level: 0\n    tmpDir: " . $directory . "/cache\nservices:\n    larastrict.classPlacement:\n        arguments:\n            applicationNamespaces: [LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming]\n";
                file_put_contents($directory . '/phpstan.neon', $configuration);
                $process = new Process([
                    PHP_BINARY,
                    $root . '/vendor/bin/phpstan',
                    'analyse',
                    '-c',
                    $directory . '/phpstan.neon',
                    '--no-progress',
                    '--error-format=json',
                    __DIR__ . '/Fixtures/Naming/Confirme/Sdk/Endpoints/Verifiers/DeviceEntity.php',
                    __DIR__ . '/Fixtures/Naming/Entities/AccountEntity.php',
                    __DIR__ . '/Fixtures/Naming/Misplaced/RequestToAccountEntity.php',
                ], $root);
                $process->setTimeout(60);
                $process->run();
                $output = $process->getOutput() . $process->getErrorOutput();
                self::assertSame(1, $process->getExitCode(), $output);
                $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
                self::assertIsArray($result);
                self::assertSame([
                    'errors' => 0,
                    'file_errors' => $extensionEnabled ? 1 : 2,
                ], $result['totals'], $output);
            }
        } finally {
            (new Filesystem())->deleteDirectory($directory);
        }
    }
}
