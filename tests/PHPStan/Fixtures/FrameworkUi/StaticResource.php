<?php declare(strict_types=1);

namespace App\Ui\Resources;

use Carbon\Factory as CarbonFactory;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Cache;

final class StaticResource extends Resource
{
    public static function schema(): void
    {
        Filament::getPanel();
        app(CarbonFactory::class);
        Cache::get('key');
        now();
        today();
        date_create();
        date_create_immutable();
        date_create_from_format('Y-m-d', '2026-01-01');
        date_create_immutable_from_format('Y-m-d', '2026-01-01');
        \Carbon\Carbon::now();
        \Carbon\CarbonImmutable::parse('2026-01-01');
        new \DateTime();
        new \DateTimeImmutable();
        new \Carbon\Carbon();
        new \Carbon\CarbonImmutable();
        $callback = static fn () => now();
    }

    private static function formatDate(): mixed
    {
        return now();
    }

    public function instanceDate(): mixed
    {
        return now();
    }
}
