<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Http\TenantApi\Store;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Actions\SaveAction;
use Wrkflow\GetValue\GetValue;

final class StoreController
{
    public function __invoke(SaveAction $action, GetValue $data): void
    {
        unset($action, $data);
    }

    private function helper(SaveAction $value): void
    {
        unset($value);
    }
}
