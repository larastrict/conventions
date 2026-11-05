<?php declare(strict_types=1);

namespace App\Billing\Actions;

use App\Billing\Entities\InvoiceEntity;
use Carbon\Factory as CarbonFactory;
use Carbon\FactoryImmutable;
use Illuminate\Database\Eloquent\Model;
use Vendor\Demo\UnrelatedBuilder;

final class AllowedDependencies
{
    public function __construct(
        private CarbonFactory $dates,
        private FactoryImmutable $immutableDates,
    )
    {
    }

    public function run(): void
    {
        $this->dates->now();
        $this->immutableDates->now();
        $invoiceEntity = new InvoiceEntity();
        unset($invoiceEntity);
        Model::query();
        (new UnrelatedBuilder())->make();
    }
}

