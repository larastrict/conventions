<?php

namespace App\Billing\Queries;

final class ExpirationsQuery
{
    public function execute(): int { return 1; }
}

namespace App\Billing\Commands;

use App\Billing\Queries\ExpirationsQuery;

final class ExpirationsCommand extends \Illuminate\Console\Command
{
    public function handle(ExpirationsQuery $query): int
    {
        return $this->buildTrend(query: $query);
    }

    private function buildTrend(ExpirationsQuery $query): int { return $query->execute(); }
}
-----
<?php

namespace App\Billing\Queries;

final class ExpirationsQuery
{
    public function execute(): int { return 1; }
}

namespace App\Billing\Commands;

use App\Billing\Queries\ExpirationsQuery;

final class ExpirationsCommand extends \Illuminate\Console\Command
{
    public function handle(ExpirationsQuery $expirationsQuery): int
    {
        return $this->buildTrend(query: $expirationsQuery);
    }

    private function buildTrend(ExpirationsQuery $query): int { return $query->execute(); }
}
