<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Services;

use Carbon\Factory as CarbonFactory;

final class ClockService
{
    public function __construct(CarbonFactory $clock)
    {
        unset($clock);
    }
}
