<?php declare(strict_types=1);

namespace App\Ui\Services;

use Carbon\Factory as CarbonFactory;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

final class ScreenService extends Component
{
    public function __construct(CarbonFactory $clock) {}

    public function boot(CarbonFactory $clock): void {}

    public function render(): void
    {
        Filament::getPanel();
        Cache::get('key');
        app(CarbonFactory::class);
        now();
    }
}
