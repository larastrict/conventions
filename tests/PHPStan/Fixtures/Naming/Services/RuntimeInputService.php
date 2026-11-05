<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Services;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Contracts\CreateOrderQueryContract;
use Wrkflow\GetValue\GetValue;

final class RuntimeInputService
{
    public function __construct(GetValue $data)
    {
        unset($data);
    }

    public function execute(CreateOrderQueryContract $runtimeValue, GetValue $data): void
    {
        unset($runtimeValue, $data);
    }

    public function helper(CreateOrderQueryContract $runtimeValue): void
    {
        unset($runtimeValue);
    }
}
