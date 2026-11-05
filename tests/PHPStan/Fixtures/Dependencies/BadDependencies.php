<?php declare(strict_types=1);

namespace App\Billing\Services;

use App\Billing\Actions\ChargeAction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class BadDependencies
{
    public function run(): void
    {
        app(ChargeAction::class);
        resolve(ChargeAction::class);
        new ChargeAction();
        new Carbon();
        Carbon::now();
        now();
        Cache::get('key');
        DB::transaction(static fn () => null);
    }
}

