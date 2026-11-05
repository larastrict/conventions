<?php declare(strict_types=1);

namespace App\Billing;

use App\Billing\Actions\ChargeAction;

final class RequestMethodDependencies extends BaseBillingRequest
{
    public function rules(): array
    {
        $this->container->make(ChargeAction::class);
        app(ChargeAction::class);
        resolve(ChargeAction::class);

        return [];
    }

    public function authorize(): bool
    {
        $this->container->make(ChargeAction::class);
        app(ChargeAction::class);
        resolve(ChargeAction::class);

        return true;
    }
}
