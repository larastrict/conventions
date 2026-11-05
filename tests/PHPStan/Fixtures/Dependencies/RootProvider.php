<?php declare(strict_types=1);

namespace App\Billing;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

final class OrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Cache::get('key');
    }
}
