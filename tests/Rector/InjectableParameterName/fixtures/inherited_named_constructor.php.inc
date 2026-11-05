<?php

namespace App\Billing\Queries;

class InheritedNamedClockQuery
{
    public function __construct(private \Carbon\Factory $datetime) {}
    public function execute(): mixed { return $this->datetime->now(); }
}

final class NamedChildClockQuery extends InheritedNamedClockQuery {}

new NamedChildClockQuery(datetime: new \Carbon\Factory());
