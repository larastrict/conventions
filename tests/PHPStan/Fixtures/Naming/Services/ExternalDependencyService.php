<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Services;

use Illuminate\Contracts\Config\Repository;

final class ExternalDependencyService
{
    public function __construct(Repository $value)
    {
        unset($value);
    }
}
