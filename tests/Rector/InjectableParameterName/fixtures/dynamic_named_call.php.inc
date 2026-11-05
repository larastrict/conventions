<?php

namespace App\Billing\Queries;

final class DynamicallyPassedQuery {}

namespace App\Billing\Commands;

use App\Billing\Queries\DynamicallyPassedQuery;

final class DynamicCallCommand extends \Illuminate\Console\Command
{
    public function handle(DynamicallyPassedQuery $query): void {}
}

function invoke(callable $callback): mixed
{
    return $callback(query: new DynamicallyPassedQuery());
}
