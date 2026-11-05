<?php

namespace App\Ui\Widgets;

final class TenantWidget extends \Livewire\Component
{
    public function panel(): mixed
    {
        return \Filament\Facades\Filament::getPanel();
    }
}

namespace App\Ui\Resources;

final class TenantResource extends \Filament\Resources\Resource
{
    public function panel(): mixed
    {
        return \Filament\Facades\Filament::getPanel();
    }
}
