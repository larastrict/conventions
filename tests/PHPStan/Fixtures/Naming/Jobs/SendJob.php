<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Jobs;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Services\SaveService;

final class SendJob implements \Illuminate\Contracts\Queue\ShouldQueue
{
    public function handle(SaveService $service): void
    {
        unset($service);
    }
}
