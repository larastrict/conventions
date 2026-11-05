<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Models\Good;

use Illuminate\Database\Eloquent\Model;

final class Order extends Model implements OrderCapability
{
    use HasCustomerRelation;

    protected $fillable = [self::AttributeStatus];
    protected $casts = [
        self::AttributeStatus => 'string',
    ];
    protected $hidden = [];

    /**
     * @var string
     */
    public $status = 'new';
}
