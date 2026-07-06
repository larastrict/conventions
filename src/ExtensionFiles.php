<?php

declare(strict_types=1);

namespace LaraStrict\Conventions;

final class ExtensionFiles
{
    public const string Ecs = __DIR__ . '/../extension-ecs.php';
    public const string Rector = __DIR__ . '/../extension-rector.php';

    /**
     * @return array<string>
     */
    public static function paths(string $baseDir, bool $laravel11Structure = false): array
    {
        return [
            $baseDir . '/app',
            $baseDir . '/database',
            $baseDir . '/lang',
            $baseDir . '/config',
            $baseDir . '/routes',
            $baseDir . '/tests',
            ...($laravel11Structure ? [
                $baseDir . '/bootstrap/app.php',
                $baseDir . '/bootstrap/providers.php',
            ] : []),
        ];
    }
}
