<?php declare(strict_types=1);

namespace App\Billing\Actions;

use App\Billing\Entities\InvoiceViewEntity;
use Illuminate\Contracts\View\Factory;

final class BadViewData
{
    public function __construct(
        private Factory $views,
    )
    {
    }

    public function render(InvoiceViewEntity|null $maybe): void
    {
        view('Billing::Invoice');
        view('Billing::Invoice', 'wrong');
        view('Billing::Invoice', [
            'data' => null,
        ]);
        view('Billing::Invoice', []);
        view('Billing::Invoice', [
            'title' => 'Invoice',
        ]);
        view('Billing::Invoice', [
            'invoice' => new InvoiceViewEntity(),
        ]);
        $this->views->make('Billing::Invoice', [
            'data' => $maybe,
        ]);
    }
}

