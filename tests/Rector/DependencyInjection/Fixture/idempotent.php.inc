<?php

namespace App\Billing\Queries;

final class CorrectQuery
{
    public function __construct(private \Carbon\Factory $carbonFactory) {}
    public function execute(): mixed { return $this->carbonFactory->now(); }
}
