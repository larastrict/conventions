<?php declare(strict_types=1);

namespace App\Billing\Actions;

use Carbon\Carbon;
use DateTimeImmutable;

final class MoreDatesAction
{
    public function execute(): void
    {
        Carbon::tomorrow();
        Carbon::createFromTimestamp(0);
        date_create();
        new DateTimeImmutable();
        Carbon::hasFormat('2026-01-01', 'Y-m-d');
    }
}
