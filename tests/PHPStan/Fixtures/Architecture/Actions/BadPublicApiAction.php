<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Architecture\Actions;

final class BadPublicApiAction
{
    public function execute(): void
    {
    }

    public function helper(): void
    {
    }
}
