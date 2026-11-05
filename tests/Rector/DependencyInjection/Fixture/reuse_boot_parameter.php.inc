<?php

namespace App\Ui\Widgets;

final class ClockWidget extends \Livewire\Component
{
    public function boot(\Carbon\Factory $clock): void
    {
        $clock->setTestNow('2026-01-01');
    }
    public function value(): mixed { return now(); }
}
-----
<?php

namespace App\Ui\Widgets;

final class ClockWidget extends \Livewire\Component
{
    public function boot(\Carbon\Factory $clock): void
    {
        $this->clock = $clock;
        $clock->setTestNow('2026-01-01');
    }
    public function value(): mixed { return $this->clock->now(); }
    protected \Carbon\Factory $clock;
}
