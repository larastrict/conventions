<?php

namespace App\Ui\Services;

final class MetricsService
{
}

namespace App\Ui\Widgets;

final class MetricsWidget extends \Livewire\Component
{
    public function values(): array
    {
        return [app(\App\Ui\Services\MetricsService::class), now()];
    }
}
-----
<?php

namespace App\Ui\Services;

final class MetricsService
{
}

namespace App\Ui\Widgets;

final class MetricsWidget extends \Livewire\Component
{
    public function values(): array
    {
        return [$this->metricsService, $this->carbonFactory->now()];
    }
    protected \App\Ui\Services\MetricsService $metricsService;
    public function boot(\App\Ui\Services\MetricsService $metricsService, \Carbon\Factory $carbonFactory): void
    {
        $this->carbonFactory = $carbonFactory;
        $this->metricsService = $metricsService;
    }
    protected \Carbon\Factory $carbonFactory;
}
