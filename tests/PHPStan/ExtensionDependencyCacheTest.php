<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan;

use Illuminate\Filesystem\Filesystem;
use PHPStan\Rules\RuleErrorBuilder;
use PHPUnit\Framework\TestCase;
use Random\Randomizer;
use Symfony\Component\Process\Process;

final class ExtensionDependencyCacheTest extends TestCase
{
    public function testSourceChangesRefreshCachedDiagnosticsWithoutApplicationChanges(): void
    {
        $directory = sys_get_temp_dir() . '/larastrict-result-cache-' . bin2hex((new Randomizer())->getBytes(8));
        $rule = <<<'PHP_WRAP'
        <?php
        namespace LarastrictCacheProbe;
        final class ChangedRule implements \PHPStan\Rules\Rule
        {
            public function getNodeType(): string
            {
                return \PhpParser\Node\Stmt\Class_::class;
            }
        
            public function processNode(\PhpParser\Node $node, \PHPStan\Analyser\Scope $scope): array
            {
                return CacheDecision::errors();
            }
        }
        PHP_WRAP;
        $helper = '<?php namespace LarastrictCacheProbe; final class CacheDecision { public static function errors(): array { return []; } }';

        try {
            foreach ([false, true] as $trackSources) {
                $project = $directory . ($trackSources ? '/tracked' : '/untracked');
                $sources = $project . '/vendor/conventions/src';
                mkdir($sources . '/PHPStan', recursive: true);
                mkdir($sources . '/Architecture');
                mkdir($project . '/app');
                $ruleFile = $sources . '/PHPStan/ChangedRule.php';
                $helperFile = $sources . '/Architecture/CacheDecision.php';
                file_put_contents($project . '/app/Consumer.php', '<?php namespace CacheProbe; final class Consumer {}');
                file_put_contents($project . '/bootstrap.php', '<?php require __DIR__ . "/vendor/conventions/src/Architecture/CacheDecision.php"; require __DIR__ . "/vendor/conventions/src/PHPStan/ChangedRule.php";');
                $configuration = "parameters:\n    level: 0\n    tmpDir: " . $project . "/cache\n    paths:\n        - " . $project . "/app\nservices:\n    cacheProbe.rule:\n        class: LarastrictCacheProbe\\ChangedRule\n        tags: [phpstan.rules.rule]\n";

                if ($trackSources) {
                    $configuration .= "    larastrict.sourceDependencies:\n        class: LaraStrict\\Conventions\\PHPStan\\ConventionsSourceDependencyRule\n        arguments:\n            sourceDirectories: [" . $sources . '/PHPStan, ' . $sources . "/Architecture]\n        tags: [phpstan.rules.rule]\n";
                }

                file_put_contents($project . '/phpstan.neon', $configuration);
                file_put_contents($ruleFile, $rule);
                file_put_contents($helperFile, str_replace('return [];', 'return [\\' . RuleErrorBuilder::class . '::message("Old convention")->identifier("larastrict.testCache")->build()];', $helper));
                $analysisLog = $this->assertAnalysis($project, 1);
                self::assertFileExists($project . '/cache/resultCache.php', $analysisLog);

                file_put_contents($helperFile, $helper);
                $this->assertAnalysis($project, $trackSources ? 0 : 1);

                if ($trackSources) {
                    self::assertStringContainsString('Result cache restored', $this->assertAnalysis($project, 0));
                }
            }
        } finally {
            (new Filesystem())->deleteDirectory($directory);
        }
    }

    private function assertAnalysis(string $project, int $expectedErrors): string
    {
        $root = dirname(__DIR__, 2);
        $process = new Process([PHP_BINARY, $root . '/vendor/bin/phpstan', 'analyse', '-c', $project . '/phpstan.neon', '--autoload-file', $project . '/bootstrap.php', '--no-progress', '--error-format=json', '-vvv'], $root);
        $process->setTimeout(60);
        $process->run();

        $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame($expectedErrors === 0 ? 0 : 1, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        self::assertIsArray($result);
        self::assertSame([
            'errors' => 0,
            'file_errors' => $expectedErrors,
        ], $result['totals'], $process->getOutput());

        return $process->getErrorOutput();
    }
}
