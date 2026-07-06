<?php

declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\UsableInContainer;

interface UnresolvableInterface
{
    public function doSomething(): void;
}
