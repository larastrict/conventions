<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\ParameterExamples;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Contracts\CreateOrderQueryContract;

final class ExtendedRunner extends BaseRunner
{
    public function helper(CreateOrderQueryContract $request): void
    {
        unset($request);
    }
}
