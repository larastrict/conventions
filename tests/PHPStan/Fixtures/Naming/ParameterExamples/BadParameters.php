<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\ParameterExamples;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Contracts\CreateOrderQueryContract;

final class BadParameters
{
    public function __construct(CreateOrderQueryContract $request)
    {
        unset($request);
    }
}
