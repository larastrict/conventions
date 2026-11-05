<?php

namespace App\Billing\Queries;

final class NamedInputQuery {}

namespace App\Billing\Commands;

use App\Billing\Queries\NamedInputQuery;

final class NamedInputCommand extends \Illuminate\Console\Command
{
    public function handle(NamedInputQuery $query): void {}
}

function invoke(NamedInputCommand $command): void
{
    $command->handle(query: new NamedInputQuery());
}
