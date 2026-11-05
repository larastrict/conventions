<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Http\TenantApi\Store;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Contracts\CreateOrderQueryContract;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Services\SaveService;
use Wrkflow\GetValue\GetValue;

final class StoreRequest extends \Illuminate\Foundation\Http\FormRequest
{
    public function rules(SaveService $service): array
    {
        return [];
    }

    public function authorize(CreateOrderQueryContract $query): bool
    {
        return true;
    }

    public function getPayload(GetValue $data): GetValue
    {
        return $data;
    }

    public function helper(SaveService $runtimeValue): void
    {
        unset($runtimeValue);
    }

    protected function validator(SaveService $service): mixed
    {
        return null;
    }

    protected function after(SaveService $service): array
    {
        return [];
    }
}
