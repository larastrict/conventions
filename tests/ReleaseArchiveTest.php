<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests;

use Illuminate\Filesystem\Filesystem;
use PharData;
use PharFileInfo;
use PHPUnit\Framework\TestCase;
use Random\Randomizer;
use RecursiveIteratorIterator;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

final class ReleaseArchiveTest extends TestCase
{
    public function testComposerAndGitArchivesContainAllRuntimeFiles(): void
    {
        $root = dirname(__DIR__);
        $temporary = sys_get_temp_dir() . '/larastrict-release-' . bin2hex((new Randomizer())->getBytes(8));
        $snapshot = $temporary . '/snapshot';
        $filesystem = new Filesystem();
        $filesystem->makeDirectory($snapshot, 0755, true);

        try {
            $required = [];
            foreach (['src', 'resources', 'docs', 'doc'] as $directory) {
                $filesystem->copyDirectory($root . '/' . $directory, $snapshot . '/' . $directory);
                foreach ($filesystem->allFiles($snapshot . '/' . $directory) as $file) {
                    $required[] = $directory . '/' . $file->getRelativePathname();
                }
            }

            foreach (['composer.json', 'LICENSE.md', 'README.md', 'extension.neon', 'extension-ecs.php', 'extension-rector.php'] as $file) {
                $filesystem->copy($root . '/' . $file, $snapshot . '/' . $file);
                $required[] = $file;
            }

            $filesystem->copy($root . '/.gitattributes', $snapshot . '/.gitattributes');
            $excluded = ['.gitattributes', '.gitignore', 'CONTRIBUTING.md', 'composer.lock', 'ecs.php', 'rector.php', 'phpstan.neon', 'phpstan-baseline.neon', 'phpunit.xml', 'phpunit.xml.dist'];
            foreach (['.github', '.gitlab', '.agents', '.idea', '.phpunit.cache', 'fixtures', 'tests', 'vendor'] as $directory) {
                $filesystem->makeDirectory($snapshot . '/' . $directory);
                $excluded[] = $directory . '/release-test.txt';
            }

            foreach ($excluded as $file) {
                if ($filesystem->exists($snapshot . '/' . $file) === false) {
                    $filesystem->put($snapshot . '/' . $file, 'Development files must not ship.');
                }
            }

            $this->runCommand(['git', 'init', '--quiet', $snapshot], $root);
            $this->runCommand(['git', 'add', '--force', '.'], $snapshot);
            $tree = trim($this->runCommand(['git', 'write-tree'], $snapshot));
            $gitArchive = $temporary . '/git.tar';
            $this->runCommand(['git', 'archive', '--format=tar', '--output=' . $gitArchive, $tree], $snapshot);

            $composer = (new ExecutableFinder())->find('composer');
            self::assertNotNull($composer, 'Composer is required to validate release archives.');
            $this->runCommand([$composer, 'archive', '--format=tar', '--dir=' . $temporary, '--file=composer', '--no-interaction', '--no-plugins', '--no-scripts'], $snapshot);

            foreach ([$gitArchive, $temporary . '/composer.tar'] as $path) {
                $archive = new PharData($path);
                $files = [];
                foreach (new RecursiveIteratorIterator($archive) as $file) {
                    self::assertInstanceOf(PharFileInfo::class, $file);
                    $files[] = substr($file->getPathname(), strlen('phar://' . $path . '/'));
                }

                $missing = array_values(array_diff($required, $files));
                self::assertSame([], $missing, $path . ' is missing runtime files.');
                self::assertSame([], array_values(array_intersect($excluded, $files)), $path . ' contains development files.');
                self::assertSame([], array_values(array_intersect([
                    'bin/larastrict-sync-ai',
                    'bin/larastrict-check-views',
                    'bin/larastrict-fix-class-names',
                ], $files)), $path . ' contains a removed command.');
            }
        } finally {
            $filesystem->deleteDirectory($temporary);
        }
    }

    /**
     * @param list<string> $arguments
     */
    private function runCommand(array $arguments, string $directory, int $exitCode = 0): string
    {
        $process = new Process($arguments, $directory);
        $process->setTimeout(60);
        $process->run();
        self::assertSame($exitCode, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());

        return $process->getOutput();
    }
}
