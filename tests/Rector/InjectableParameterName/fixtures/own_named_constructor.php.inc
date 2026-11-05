<?php

namespace App\Billing\Queries;

final class NamedClockQuery
{
    public function __construct(private \Carbon\Factory $datetime) {}
    public function execute(): mixed { return $this->datetime->now(); }
}

new NamedClockQuery(datetime: new \Carbon\Factory());
