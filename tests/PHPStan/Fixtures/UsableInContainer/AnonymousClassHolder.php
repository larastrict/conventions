<?php

declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\UsableInContainer;

final class AnonymousClassHolder
{
    public function make(): object
    {
        return new class() {
            public function __construct(
                public string $requiredValue = 'default',
            ) {
            }
        };
    }
}
