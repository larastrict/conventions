<?php

namespace App\Billing\Queries;

final class ForwardedQuery {}

namespace App\Billing\Commands;

use App\Billing\Queries\ForwardedQuery;

final class ForwardingCommand extends \Illuminate\Console\Command
{
    public function handle(ForwardedQuery $query): void {}
}

function invoke(\ReflectionMethod $method, ForwardingCommand $command): mixed
{
    return $method->invoke($command, query: new ForwardedQuery());
}
