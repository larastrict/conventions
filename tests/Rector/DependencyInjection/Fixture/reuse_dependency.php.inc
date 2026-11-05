<?php

namespace App\Billing\Queries;

final class ExistingQuery
{
    public function __construct(private \Carbon\Factory $clock) {}
    public function execute(): mixed { return now(); }
}
-----
<?php

namespace App\Billing\Queries;

final class ExistingQuery
{
    public function __construct(private \Carbon\Factory $clock) {}
    public function execute(): mixed { return $this->clock->now(); }
}
