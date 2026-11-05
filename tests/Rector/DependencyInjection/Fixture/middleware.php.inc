<?php

namespace App\Http\Middlewares;

final class TenantCookieMiddleware
{
    public function handle(): void
    {
        \Filament\Facades\Filament::getPanel();
        \Illuminate\Support\Facades\Cookie::queue('tenant', 'id', 30);
    }
}
-----
<?php

namespace App\Http\Middlewares;

final class TenantCookieMiddleware
{
    public function handle(): void
    {
        $this->filamentManager->getPanel();
        $this->cookieFactory->queue('tenant', 'id', 30);
    }
    public function __construct(private \Filament\FilamentManager $filamentManager, private \Illuminate\Contracts\Cookie\QueueingFactory $cookieFactory)
    {
    }
}
