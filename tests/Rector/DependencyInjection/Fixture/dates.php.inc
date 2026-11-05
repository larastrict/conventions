<?php

namespace App\Billing\Queries;

final class DateQuery
{
    public function execute(): array
    {
        return [now(), new \DateTimeImmutable(), \Carbon\Carbon::parse('2026-01-01')];
    }
}
-----
<?php

namespace App\Billing\Queries;

final class DateQuery
{
    public function execute(): array
    {
        return [$this->carbonFactory->now(), $this->carbonFactoryImmutable->now()->toDateTimeImmutable(), $this->carbonFactory->parse('2026-01-01')];
    }
    public function __construct(private \Carbon\Factory $carbonFactory, private \Carbon\FactoryImmutable $carbonFactoryImmutable)
    {
    }
}
