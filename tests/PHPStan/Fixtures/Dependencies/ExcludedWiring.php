<?php declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Cache;

final class ExcludedWiring
{
    public function register(): void
    {
        Cache::get('key');
    }
}
