<?php declare(strict_types=1);

namespace Livewire;

abstract class Component
{}

namespace Filament\Resources;

abstract class Resource
{}

namespace Filament;

abstract class PanelProvider extends \Illuminate\Support\ServiceProvider
{}

namespace Filament\Facades;

final class Filament
{
    public static function getPanel(): string
    {
        return 'panel';
    }
}

namespace Filament\Actions;

final class Action
{
    public function url(\Closure $callback): self
    {
        return $this;
    }
}
