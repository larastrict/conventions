<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Http\TenantWeb;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Actions\SaveAction;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Services\SaveService;

final class VerifyController
{
    public function __construct(SaveService $service, string $requiredValue) {}

    public function __invoke(SaveAction $action): void {}
}
