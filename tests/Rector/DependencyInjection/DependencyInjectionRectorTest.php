<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\Rector\DependencyInjection;

use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;
use Symfony\Component\Process\Process;

final class DependencyInjectionRectorTest extends AbstractRectorTestCase
{
    #[DataProvider('provideData')]
    public function test(string $file): void
    {
        $this->doTestFile($file);
    }

    public static function provideData(): Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    public function testTransformedWidgetsRestoreDependenciesOnEachBoot(): void
    {
        $this->runExpectedFixture('livewire.php.inc', <<<'PHP'
$widget = new \App\Ui\Widgets\MetricsWidget();
$first = new \App\Ui\Services\MetricsService();
$second = new \App\Ui\Services\MetricsService();
$factory = new \Carbon\Factory();
$widget->boot($first, $factory);
if ($widget->values()[0] !== $first) { throw new \RuntimeException('Initial boot injection failed.'); }
$widget->boot($second, $factory);
if ($widget->values()[0] !== $second || get_object_vars($widget) !== []) { throw new \RuntimeException('Hydration dependency restoration failed.'); }
PHP);
    }

    public function testTransformedDateCreationKeepsNativeImmutabilityAndInjectedClocks(): void
    {
        $this->runExpectedFixture('dates.php.inc', <<<'PHP'
$mutable = new \Carbon\Factory();
$mutable->setTestNow('2026-01-02 10:00:00');
$immutable = new \Carbon\FactoryImmutable();
$immutable->setTestNow('2026-01-03 10:00:00');
$query = new \App\Billing\Queries\DateQuery($mutable, $immutable);
$dates = $query->execute();
if ($dates[0]->format('Y-m-d H:i:s') !== '2026-01-02 10:00:00'
    || $dates[1]::class !== \DateTimeImmutable::class
    || $dates[1]->format('Y-m-d H:i:s') !== '2026-01-03 10:00:00'
    || $dates[1] === $dates[1]->add(new \DateInterval('PT1H'))) {
    throw new \RuntimeException('Date mutability or injected clock changed.');
}
PHP);
    }

    public function testInheritedFactoryKeepsClockSettingsAndMutableResults(): void
    {
        $this->runExpectedFixture('inherited_factory.php.inc', <<<'PHP'
$factory = new \Carbon\FactoryImmutable(['timezone' => 'Europe/Prague']);
$factory->setTestNow('2026-01-02 10:00:00');
$query = new \App\Billing\Queries\InheritedClockQuery($factory);
$date = $query->execute();
if (! $date instanceof \Carbon\Carbon || $date->format('Y-m-d H:i:s e') !== '2026-01-02 10:00:00 Europe/Prague'
    || $date !== $date->addHour() || ! $query->immutable() instanceof \Carbon\CarbonImmutable) {
    throw new \RuntimeException('Inherited clock settings or result mutability changed.');
}
PHP);
    }

    private function runExpectedFixture(string $name, string $assertions): void
    {
        $fixture = file_get_contents(__DIR__ . '/Fixture/' . $name);
        self::assertIsString($fixture);
        $parts = explode('-----', $fixture);
        $expected = var_export(substr(trim($parts[1]), 5), true);
        $autoload = var_export(dirname(__DIR__, 3) . '/vendor/autoload.php', true);
        $bootstrap = var_export(__DIR__ . '/config/bootstrap.php', true);
        $process = new Process([PHP_BINARY, '-r', "require {$autoload}; require {$bootstrap}; eval({$expected}); {$assertions}"]);
        $process->run();
        self::assertSame(0, $process->getExitCode(), $process->getErrorOutput());
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_rule.php';
    }
}
