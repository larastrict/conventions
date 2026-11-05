<?php declare(strict_types=1);

namespace App\Billing;

use App\Billing\Actions\ChargeAction;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

final class InvalidBillingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        app(ChargeAction::class);
        resolve(ChargeAction::class);
        Container::getInstance()->make(ChargeAction::class);
    }

    public function inspectOtherRequest(self $request): void
    {
        $request->container->make(ChargeAction::class);
    }
}

final class InvalidBillingResource extends JsonResource
{
    protected ContainerContract $container;

    public function toArray($request): array
    {
        app(ChargeAction::class);
        resolve(ChargeAction::class);
        now();

        return [];
    }
}

final class InvalidBillingJob implements ShouldQueue
{
    public function handle(ContainerContract $container): void
    {
        app(ChargeAction::class);
        $container->make(ChargeAction::class);
    }

    public function failed(?Throwable $exception): void
    {
        now();
        Cache::get('key');
        DB::transaction(static fn () => null);
        new ChargeAction();
    }
}

final class BillingService
{
    public function __construct(
        private ContainerContract $container,
    )
    {
    }

    public function failed(): void
    {
        app(ChargeAction::class);
        resolve(ChargeAction::class);
        $this->container->make(ChargeAction::class);
    }
}
