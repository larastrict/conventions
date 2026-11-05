<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Services;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Contracts\CreateOrderQueryContract;

final class BadParameterService
{
    public function __construct(CreateOrderQueryContract $request)
    {
        unset($request);
    }
}
