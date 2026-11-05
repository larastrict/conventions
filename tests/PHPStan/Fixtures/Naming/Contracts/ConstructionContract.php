<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Contracts;

interface ConstructionContract
{
    public function __construct(CreateOrderQueryContract $inheritedValue);
}
