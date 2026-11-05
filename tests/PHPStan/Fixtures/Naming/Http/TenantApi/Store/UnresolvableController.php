<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Http\TenantApi\Store;

final class UnresolvableController
{
    public function __construct(string $requiredValue)
    {
        unset($requiredValue);
    }
}
