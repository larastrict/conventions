<?php

namespace App\Billing\Queries;

final class ClockNameQuery
{
    public function __construct(private \Carbon\Factory $datetime) {}
    public function execute(): mixed { return $this->datetime->now(); }
}
-----
<?php

namespace App\Billing\Queries;

final class ClockNameQuery
{
    public function __construct(private \Carbon\Factory $carbonFactory) {}
    public function execute(): mixed { return $this->carbonFactory->now(); }
}
