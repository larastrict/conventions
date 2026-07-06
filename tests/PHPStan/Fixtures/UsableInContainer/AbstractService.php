<?php

declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\UsableInContainer;

abstract class AbstractService
{
    public function __construct(
        public string $requiredValue,
    )
    {
    }
}
