<?php declare(strict_types=1);

namespace App\Filament\Helpers;

use App\Billing\Actions\ChargeAction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class ModelAttributeHelper
{
    public static function convert(): void
    {
        Carbon::instance(new \DateTimeImmutable());
        Carbon::parse('2026-01-01');
        now();
        app(ChargeAction::class);
        Cache::get('key');
        new ChargeAction();
        DB::transaction(static fn () => today());
    }

    public function instanceMethod(): void
    {
        now();
    }
}

final class ValueConversion
{
    public static function convert(): void
    {
        Carbon::parse('2026-01-01');
    }
}

namespace App\Support;

final class DateHelper
{
    public static function convert(): void
    {
        \Carbon\Carbon::parse('2026-01-01');
    }
}

final class DateService
{
    public static function convert(): void
    {
        now();
    }
}

final class CallbackHelper
{
    public static function configure(): void
    {
        (new \Filament\Actions\Action())
            ->url(static function (): string {
                app(\Carbon\Factory::class);

                return '/';
            });
    }
}
