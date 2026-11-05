<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Models\Bad;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\Models\Good\Order;

final class ProjectedColumns
{
    /** @param ProjectionBuilder $query */
    public function read(ProjectionBuilder $query): void
    {
        $query->selectRaw('MAX(created_at) as latest')->value('status');
        $query->selectRaw('MAX(created_at) -- as status')->value('status');
        $query->value(...);
        $query->selectRaw('COUNT(*) as item_count')->value('item_count');
        $column = 'created_at';
        $query->selectRaw("MAX({$column}) as latest_at")->value('latest_at');
    }
}

/** @extends \Illuminate\Database\Eloquent\Builder<Order> */
final class ProjectionBuilder extends \Illuminate\Database\Eloquent\Builder
{
    public function selectRaw($expression, array $bindings = []): static
    {
        return $this;
    }
}
