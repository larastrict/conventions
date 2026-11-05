<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Architecture\Actions;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\Architecture\Support\ActionBase;

final class InheritedPublicApiAction extends ActionBase
{
    public function execute(): void
    {
    }
}
