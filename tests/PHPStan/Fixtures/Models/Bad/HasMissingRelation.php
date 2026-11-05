<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Models\Bad;

use Illuminate\Database\Eloquent\Relations\HasMany;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Models\Support\Customer;

trait HasMissingRelation
{
    /**
     * @return HasMany<Customer, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
