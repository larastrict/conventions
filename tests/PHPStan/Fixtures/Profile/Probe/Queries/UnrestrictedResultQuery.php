<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Profile\Probe\Queries;

use Illuminate\Support\Collection;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Profile\Probe\Models\Record;

final class UnrestrictedResultQuery
{
    /**
     * @return array<string, string>|Record|Collection<int, int>|string|null
     */
    public function execute(int $mode): array|Record|Collection|string|null
    {
        return match ($mode) {
            0 => [
                'any' => 'record',
            ],
            1 => new Record(),
            2 => new Collection([1, 2]),
            3 => 'scalar',
            default => null,
        };
    }
}
