<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Listeners;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Contracts\CreateOrderQueryContract;

final class QueuedListener implements \Illuminate\Contracts\Queue\ShouldQueue
{
    public function __construct(CreateOrderQueryContract $query)
    {
        unset($query);
    }

    public function handle(CreateOrderQueryContract $event): void
    {
        unset($event);
    }
}
