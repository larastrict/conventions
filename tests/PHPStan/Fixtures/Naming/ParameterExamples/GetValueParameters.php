<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\ParameterExamples;

use Wrkflow\GetValue\GetValue;

final class GetValueParameters
{
    public function __construct(GetValue $body, GetValue $getValue)
    {
        unset($body, $getValue);
    }

    public function payload(GetValue $payload): void
    {
        unset($payload);
    }
}
