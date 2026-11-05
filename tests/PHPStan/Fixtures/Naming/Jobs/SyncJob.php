<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Jobs;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Contracts\CreateOrderQueryContract;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Services\SaveService;

final class SyncJob
{
    public function __construct(CreateOrderQueryContract $runtimeValue)
    {
        unset($runtimeValue);
    }

    public function handle(SaveService $service): void
    {
        unset($service);
    }
}
