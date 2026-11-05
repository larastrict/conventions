<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Http\TenantApi\Login;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Services\SaveService;

final class LoginQuery
{
    public function __construct(LoginAction $action, SaveService $service, string $requiredValue) {}

    public function execute(string $input): string
    {
        return $input;
    }

    public function helper(): void {}
}
