<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\ParameterExamples;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Models\Entity;

class BaseRunner
{
    public function base(Entity $entity): void
    {
        unset($entity);
    }
}
