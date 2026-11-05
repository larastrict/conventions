<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Services;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Contracts\ConstructionContract;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Contracts\CreateOrderQueryContract;

final class InheritedConstructorService implements ConstructionContract
{
    public function __construct(CreateOrderQueryContract $inheritedValue, ?SaveService $helper = null)
    {
        unset($inheritedValue, $helper);
    }
}
