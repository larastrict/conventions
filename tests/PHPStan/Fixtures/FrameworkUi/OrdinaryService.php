<?php declare(strict_types=1);

namespace App\Ui\Services;

use Filament\Facades\Filament;

final class OrdinaryService
{
    public function execute(): void
    {
        Filament::getPanel();
    }
}
