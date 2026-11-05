<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Core\Capabilities\LazyPaginator;

final class LazyPaginatorFactory
{
    public function __construct(LoadPageQuery $query, PaginationCursor $input) {}

    public function create(): LazyPaginator
    {
        return new LazyPaginator();
    }
}
