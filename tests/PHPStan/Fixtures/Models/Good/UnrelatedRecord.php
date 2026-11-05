<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Models\Good;

final class UnrelatedRecord
{
    public function where(string $column): self
    {
        return $this;
    }

    public function with(string $relation): self
    {
        return $this;
    }
}
