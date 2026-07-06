<?php

declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\UsableInContainer;

final readonly class UnresolvableService
{
    public function __construct(
        public string $requiredValue,
    )
    {
    }
}
