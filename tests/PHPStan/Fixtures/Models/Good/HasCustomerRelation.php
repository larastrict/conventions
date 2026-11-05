<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Models\Good;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Models\Support\Customer;

trait HasCustomerRelation
{
    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
