<?php

namespace App\Billing\Queries;

abstract class ClockQuery
{
    public function __construct(protected \Carbon\FactoryImmutable $carbonFactory) {}
}

final class InheritedClockQuery extends ClockQuery
{
    public function execute(): mixed { return now(); }
    public function immutable(): mixed { return \Carbon\CarbonImmutable::now(); }
}
-----
<?php

namespace App\Billing\Queries;

abstract class ClockQuery
{
    public function __construct(protected \Carbon\FactoryImmutable $carbonFactory) {}
}

final class InheritedClockQuery extends ClockQuery
{
    public function execute(): mixed { return $this->carbonFactory->now()->toMutable(); }
    public function immutable(): mixed { return $this->carbonFactory->now(); }
}
