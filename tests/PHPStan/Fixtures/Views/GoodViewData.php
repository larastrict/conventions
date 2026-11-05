<?php declare(strict_types=1);

namespace App\Billing\Actions;

use App\Billing\Entities\InvoiceViewEntity;
use Illuminate\Contracts\View\Factory;

final class GoodViewData
{
    public function __construct(
        private Factory $views,
    )
    {
    }

    public function render(InvoiceViewEntity $data): void
    {
        view('Billing::Invoice', [
            'data' => $data,
            'title' => 'Invoice',
        ]);
        $this->views->make('Billing::Invoice', [
            'data' => $data,
        ]);
        view(view: 'Billing::Invoice', data: [
            'data' => $data,
            'title' => 'Invoice',
        ]);
        $this->views->make(view: 'Billing::Invoice', data: [
            'data' => $data,
        ]);
        view('vendor::package::view');
        view('pagination::links');
    }
}

