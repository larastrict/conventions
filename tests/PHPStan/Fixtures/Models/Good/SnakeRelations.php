<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Models\Good;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Models\Support\Customer;

final class SnakeRelations extends Model
{
    public const string RelationOrderItems = 'order_items';

    /**
     * @return HasMany<Customer, $this>
     */
    public function order_items(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
