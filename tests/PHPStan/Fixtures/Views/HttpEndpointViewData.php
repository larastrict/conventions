<?php declare(strict_types=1);

namespace App\Billing\Actions;

use App\Billing\Http\TenantApi\Login\LoginEntity;

final class HttpEndpointViewData
{
    public function render(LoginEntity $login): void
    {
        view('Billing::Login', [
            'data' => $login,
        ]);
    }
}
