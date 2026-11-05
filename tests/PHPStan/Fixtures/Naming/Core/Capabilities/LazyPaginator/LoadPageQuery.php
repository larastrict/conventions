<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Core\Capabilities\LazyPaginator;

final class LoadPageQuery
{
    public function __construct(\Carbon\Factory $clock, string $requiredValue) {}

    public function execute(PaginationCursor $input): void {}

    public function helper(): void {}
}
