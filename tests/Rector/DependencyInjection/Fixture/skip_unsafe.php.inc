<?php

namespace App\Ui\Resources;

final class ProjectResource extends \Filament\Resources\Resource
{
    public static function query(): mixed
    {
        return now();
    }
}

namespace App\Ui\Queries;

final class OptionalQuery
{
    public function __construct(private string $option = 'default') {}
    public function execute(): mixed { return now(); }
}

final class CallbackQuery
{
    public function execute(): mixed { return static fn () => now(); }
    public static function helper(): mixed { return now(); }
}

class ParentQuery
{
    public function __construct(private string $payload) {}
}

final class ChildQuery extends ParentQuery
{
    public function execute(): mixed { return now(); }
}

final class CollisionQuery
{
    private string $carbonFactory;
    public function execute(): mixed { return now(); }
}

class ParentWidget extends \Livewire\Component
{
    public function boot(): void {}
}

final class InheritedWidget extends ParentWidget
{
    public function value(): mixed { return now(); }
}
