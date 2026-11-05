<?php

namespace App\Ui\Widgets;

final class BootWidget extends \Livewire\Component
{
    public bool $initialized = false;
    public function boot(): void
    {
        $this->initialized = true;
    }
    public function value(): mixed { return now(); }
}
-----
<?php

namespace App\Ui\Widgets;

final class BootWidget extends \Livewire\Component
{
    public bool $initialized = false;
    public function boot(\Carbon\Factory $carbonFactory): void
    {
        $this->carbonFactory = $carbonFactory;
        $this->initialized = true;
    }
    public function value(): mixed { return $this->carbonFactory->now(); }
    protected \Carbon\Factory $carbonFactory;
}
