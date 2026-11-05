<?php declare(strict_types=1);

namespace App\Billing\Actions;

use App\Billing\Entities\InvoicePage;

final class UnsuffixedViewData
{
    public function render(InvoicePage $invoicePage): void
    {
        view('Billing::Invoice', [
            'data' => $invoicePage,
        ]);
    }
}
