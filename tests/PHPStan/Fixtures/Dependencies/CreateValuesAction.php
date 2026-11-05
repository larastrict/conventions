<?php declare(strict_types=1);

namespace App\Billing\Actions;

use App\Billing\Entities\PaymentGateway;
use App\Billing\Models\Gateway;

final class CreateValuesAction
{
    /**
     * @return list<object>
     */
    public function execute(): array
    {
        return [new PaymentGateway(), new Gateway()];
    }
}
