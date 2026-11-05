<?php declare(strict_types=1);

namespace Vendor\Demo;

final class UnrelatedMethods
{
    public function make(): void
    {
        now();
        app();
    }
}
