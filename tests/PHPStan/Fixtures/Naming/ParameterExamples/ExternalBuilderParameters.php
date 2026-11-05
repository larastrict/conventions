<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\ParameterExamples;

use Lcobucci\JWT\Builders\Builder;

final class ExternalBuilderParameters
{
    public function __construct(Builder $value)
    {
        unset($value);
    }
}
