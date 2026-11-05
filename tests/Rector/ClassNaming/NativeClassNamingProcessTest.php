<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\Rector\ClassNaming;

use Illuminate\Filesystem\Filesystem;
use LaraStrict\Conventions\PHPStan\ClassPlacementPolicy;
use LaraStrict\Conventions\Rector\ClassNamingRector;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Extensions\SdkPlacementExtension;
use PHPUnit\Framework\TestCase;
use Random\Randomizer;
use Rector\Config\RectorConfig;
use Symfony\Component\Process\Process;

final class NativeClassNamingProcessTest extends TestCase
{
    public function testNativeRectorRenamesCodeAndLeavesFileMovesToTheImplementer(): void
    {
        $root = sys_get_temp_dir() . '/larastrict-class-names-test-' . bin2hex((new Randomizer())->getBytes(6));
        $files = new Filesystem();
        $package = dirname(__DIR__, 3);
        $files->makeDirectory($root . '/vendor/bin', 0755, true);
        $files->makeDirectory($root . '/app/Billing/Models/Scopes', 0755, true);
        $files->makeDirectory($root . '/app/Billing/Queries', 0755, true);
        $files->makeDirectory($root . '/app/Billing/Entities', 0755, true);
        $files->makeDirectory($root . '/app/Billing/Sdk/Endpoints/Verifiers', 0755, true);

        try {
            file_put_contents($root . '/composer.json', json_encode([
                'autoload' => [
                    'psr-4' => [
                        'App\\' => 'app/',
                    ],
                ],
            ], JSON_THROW_ON_ERROR));
            file_put_contents($root . '/vendor/autoload.php', '<?php $loader = require ' . var_export($package . '/vendor/autoload.php', true) . '; $loader->addPsr4("App\\\\", ' . var_export($root . '/app', true) . '); return $loader;');
            symlink($package . '/vendor/bin/rector', $root . '/vendor/bin/rector');
            $scope = <<<'PHP'
<?php
namespace App\Billing\Models\Scopes;
class Active implements \Illuminate\Database\Eloquent\Scope
{
    public function apply(\Illuminate\Database\Eloquent\Builder $builder, \Illuminate\Database\Eloquent\Model $model): void {}
}
PHP;
            file_put_contents($root . '/app/Billing/Models/Scopes/Active.php', $scope);
            // A target collision must never overwrite another declaration.
            file_put_contents($root . '/app/Billing/Models/Scopes/Blocked.php', str_replace('class Active ', 'class Blocked ', $scope));
            file_put_contents($root . '/app/Billing/Models/Scopes/BlockedScope.php', str_replace('class Active ', 'class BlockedScope ', $scope));
            file_put_contents($root . '/app/Billing/Entities/Order.php', '<?php namespace App\Billing\Entities; final class Order {}');
            file_put_contents($root . '/app/Billing/InvoiceTransformer.php', '<?php namespace App\Billing; final class InvoiceTransformer {}');
            file_put_contents($root . '/app/Billing/Queries/FindQuery.php', <<<'PHP'
<?php
namespace App\Billing\Queries;
use App\Billing\Models\Scopes\Active;
final class FindQuery
{
    public function execute(): Active { return new Active(); }
}
PHP);
            $sdkEntity = '<?php namespace App\\Billing\\Sdk\\Endpoints\\Verifiers; final readonly class DeviceEntity {}';
            file_put_contents($root . '/app/Billing/Sdk/Endpoints/Verifiers/DeviceEntity.php', $sdkEntity);
            file_put_contents($root . '/rector.php', '<?php return ' . RectorConfig::class . "::configure()->withPaths([__DIR__ . '/app'])->withRules([" . ClassNamingRector::class . '::class])->withConfiguredRule(' . ClassNamingRector::class . '::class, ["policy" => new ' . ClassPlacementPolicy::class . '(extensions: [new ' . SdkPlacementExtension::class . '()])]);');
            $unrelated = '<?php return new \DateTimeImmutable();';
            file_put_contents($root . '/unrelated.php', $unrelated);
            $original = $this->snapshot($root, $files);
            $preview = new Process([PHP_BINARY, $root . '/vendor/bin/rector', 'process', '--dry-run', '--clear-cache', '--no-progress-bar'], $root);
            $preview->run();
            self::assertSame(2, $preview->getExitCode(), $preview->getOutput() . $preview->getErrorOutput());
            self::assertStringContainsString('ActiveScope', $preview->getOutput());
            self::assertSame($original, $this->snapshot($root, $files), 'A preview must not change project files.');

            $apply = new Process([PHP_BINARY, $root . '/vendor/bin/rector', 'process', '--clear-cache', '--no-progress-bar'], $root);
            $apply->run();
            self::assertSame(0, $apply->getExitCode(), $apply->getOutput() . $apply->getErrorOutput());
            self::assertSame($unrelated, file_get_contents($root . '/unrelated.php'));
            self::assertSame($sdkEntity, file_get_contents($root . '/app/Billing/Sdk/Endpoints/Verifiers/DeviceEntity.php'));
            self::assertDirectoryDoesNotExist($root . '/app/Billing/Sdk/Endpoints/Verifiers/Entities');
            self::assertFileExists($root . '/app/Billing/Models/Scopes/Active.php');
            self::assertFileDoesNotExist($root . '/app/Billing/Models/Scopes/ActiveScope.php');
            // The implementer makes the corresponding file moves after Rector.
            rename($root . '/app/Billing/Models/Scopes/Active.php', $root . '/app/Billing/Models/Scopes/ActiveScope.php');
            rename($root . '/app/Billing/Entities/Order.php', $root . '/app/Billing/Entities/OrderEntity.php');
            $files->makeDirectory($root . '/app/Billing/Transformers', 0755, true);
            rename($root . '/app/Billing/InvoiceTransformer.php', $root . '/app/Billing/Transformers/InvoiceTransformer.php');
            self::assertFileExists($root . '/app/Billing/Models/Scopes/ActiveScope.php');
            self::assertFileExists($root . '/app/Billing/Entities/OrderEntity.php');
            self::assertFileExists($root . '/app/Billing/Transformers/InvoiceTransformer.php');
            self::assertFileExists($root . '/app/Billing/Models/Scopes/Blocked.php');
            $query = file_get_contents($root . '/app/Billing/Queries/FindQuery.php');
            self::assertIsString($query);
            self::assertStringContainsString('ActiveScope', $query);
            self::assertStringNotContainsString('new Active()', $query);

            $after = $this->snapshot($root, $files);
            $repeat = new Process([PHP_BINARY, $root . '/vendor/bin/rector', 'process', '--dry-run', '--clear-cache', '--no-progress-bar'], $root);
            $repeat->run();
            self::assertSame(0, $repeat->getExitCode(), $repeat->getOutput() . $repeat->getErrorOutput());
            self::assertSame($after, $this->snapshot($root, $files));
        } finally {
            $files->deleteDirectory($root);
        }
    }

    /**
     * @return array<string, string>
     */
    private function snapshot(string $root, Filesystem $files): array
    {
        $snapshot = [];
        foreach ($files->allFiles($root . '/app') as $file) {
            $contents = file_get_contents($file->getPathname());
            self::assertIsString($contents);
            $snapshot[$file->getRelativePathname()] = hash('sha256', $contents);
        }

        ksort($snapshot);

        return $snapshot;
    }
}
