<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Models\Bad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Models\Support\Customer;

final class Invoice extends Model implements InvoiceCapability
{
    use HasMissingRelation;

    protected $fillable = ['id', 'number'];
    protected $guarded = ['owner_id'];
    protected $hidden = ['secret'];
    protected $visible = ['name'];
    protected $casts = [
        'created_at' => 'immutable_datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\LaraStrict\ConventionsTests\PHPStan\Fixtures\Models\Support\Customer, $this>
     */
    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<Customer, $this>
     */
    public function missingRelation(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * @return HasMany<Customer, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
