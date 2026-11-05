<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Architecture\Actions;

use LaraStrict\ConventionsTests\External\Contracts\NamedRouteActionContract;

final class NamedRouteAction implements NamedRouteActionContract
{
    public function execute(): void
    {
    }

    public function getFileSuffix(): string
    {
        return 'route';
    }
}
