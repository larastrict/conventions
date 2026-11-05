<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Models\Bad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Models\Support\Customer;

final class CamelRelations extends Model
{
    use HasOrderItems;

    public const string RelationCustomerAccount = 'customerAccount';
    public const string RelationOrderItems = 'orderItems';

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customerAccount(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function displayName(): string
    {
        return 'Unrelated method names keep their convention.';
    }
}
