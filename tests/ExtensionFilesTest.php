<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests;

use LaraStrict\Conventions\ExtensionFiles;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ExtensionFilesTest extends TestCase
{
    public function testEcsConstantPointsToExistingFile(): void
    {
        self::assertFileExists(ExtensionFiles::Ecs);
        self::assertSame(realpath(__DIR__ . '/../extension-ecs.php'), realpath(ExtensionFiles::Ecs));
    }

    public function testRectorConstantPointsToExistingFile(): void
    {
        self::assertFileExists(ExtensionFiles::Rector);
        self::assertSame(realpath(__DIR__ . '/../extension-rector.php'), realpath(ExtensionFiles::Rector));
    }

    public function testPathsWithoutLaravel11Structure(): void
    {
        $baseDir = '/var/www/app';

        self::assertSame([
            $baseDir . '/app',
            $baseDir . '/database',
            $baseDir . '/lang',
            $baseDir . '/config',
            $baseDir . '/routes',
            $baseDir . '/tests',
        ], ExtensionFiles::paths($baseDir));
    }

    public function testPathsWithLaravel11Structure(): void
    {
        $baseDir = '/var/www/app';

        self::assertSame([
            $baseDir . '/app',
            $baseDir . '/database',
            $baseDir . '/lang',
            $baseDir . '/config',
            $baseDir . '/routes',
            $baseDir . '/tests',
            $baseDir . '/bootstrap/app.php',
            $baseDir . '/bootstrap/providers.php',
        ], ExtensionFiles::paths($baseDir, laravel11Structure: true));
    }

    public function testPathsWithLaravel11StructureFalseMatchesDefault(): void
    {
        $baseDir = '/tmp/project';

        self::assertSame(
            ExtensionFiles::paths($baseDir),
            ExtensionFiles::paths($baseDir, laravel11Structure: false),
        );
    }

    #[DataProvider('baseDirProvider')]
    public function testPathsPrependsBaseDirToEveryEntry(string $baseDir): void
    {
        foreach (ExtensionFiles::paths($baseDir, laravel11Structure: true) as $path) {
            self::assertStringStartsWith($baseDir . '/', $path);
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function baseDirProvider(): array
    {
        return [
            'absolute unix path' => ['/var/www/app'],
            'relative path' => ['./project'],
            'empty string' => [''],
        ];
    }
}
