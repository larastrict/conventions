<?php

declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\UsableInContainerOther;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\UsableInContainer\BaseCommand;

final class ChildCommand extends BaseCommand
{
    public function __construct(
        public string $requiredValue,
    )
    {
    }
}
