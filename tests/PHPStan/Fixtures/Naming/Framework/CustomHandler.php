<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Framework;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Services\SaveService;

final class CustomHandler extends InjectionHandler
{
    public function process(SaveService $value): void {}
}
