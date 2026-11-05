<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Architecture\Actions;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\Architecture\Contracts\LocalSuffixContract;

final class LocalContractAction implements LocalSuffixContract
{
    public function execute(): void
    {
    }

    public function getFileSuffix(): string
    {
        return 'route';
    }
}
