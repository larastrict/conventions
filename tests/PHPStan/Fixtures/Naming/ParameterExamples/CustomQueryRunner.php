<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\ParameterExamples;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Contracts\CreateOrderQueryContract;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Models\Entity;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Models\OtherEntity;

final class CustomQueryRunner implements QueryRunner
{
    public function __construct(CreateOrderQueryContract $request)
    {
        unset($request);
    }

    public function run(CreateOrderQueryContract $request): void
    {
        unset($request);
    }

    public function ambiguous(Entity|OtherEntity $value): void
    {
        unset($value);
    }

    public function scalar(string $value): void
    {
        unset($value);
    }
}
