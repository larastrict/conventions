<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Queries\Builders;

use Illuminate\Database\Eloquent\Builder;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Models\Customer;

/**
 * @extends Builder<Customer>
 */
final class RuntimeBuilder extends Builder
{
}
