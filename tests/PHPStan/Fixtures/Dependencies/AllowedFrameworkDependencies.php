<?php declare(strict_types=1);

namespace App\Billing;

use App\Billing\Actions\ChargeAction;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Throwable;

abstract class BaseBillingRequest extends FormRequest
{
}

final class BillingRequest extends BaseBillingRequest
{
    protected function prepareForValidation(): void
    {
        $this->container->make(ChargeAction::class);
    }

    public function rules(ChargeAction $chargeAction): array
    {
        $chargeAction->execute();

        return [];
    }

    public function authorize(ChargeAction $chargeAction): bool
    {
        $chargeAction->execute();

        return true;
    }
}

abstract class BaseBillingResource extends JsonResource
{
    protected ContainerContract $container;
}

final class BillingResource extends BaseBillingResource
{
    public function toArray($request): array
    {
        $this->container->make(ChargeAction::class);

        return [];
    }
}

final class BillingResourceCollection extends ResourceCollection
{
    protected ContainerContract $container;

    public function toArray($request): array
    {
        $this->container->make(ChargeAction::class);

        return [];
    }
}

abstract class BaseBillingJob implements ShouldQueue
{
}

final class BillingJob extends BaseBillingJob
{
    public function failed(?Throwable $exception): void
    {
        app(ChargeAction::class);
        resolve(ChargeAction::class);
        app()
            ->make(ChargeAction::class);
        Container::getInstance()->make(ChargeAction::class);
    }
}

final class BillingListener implements ShouldQueue
{
    public function failed(object $event, Throwable $exception): void
    {
        app(ChargeAction::class);
        resolve(ChargeAction::class);
    }
}
