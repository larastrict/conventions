<?php declare(strict_types=1);

namespace App\RectorTests\Identifier;

use Illuminate\Database\Eloquent\Model;

final class Invoice extends Model
{
    public const AttributeStatus = 'status';
    public const RelationCustomer = 'customer';
    private const AttributeSecret = 'secret';

    protected $fillable = ['status', 'unmapped'];
    protected $hidden = ['status', 'remember_token'];
    protected $casts = ['status' => 'string'];
}

function query(Invoice $invoice): void
{
    $invoice->where('status', 'active');
    $invoice->with('customer');
    $invoice->where('unmapped', 'active');
    $invoice->where('secret', 'value');
    $callback = $invoice->where(...);
    $projected = $invoice->newQuery()->selectRaw('MAX(orders.created_at) as last_verification_at', [])->value('last_verification_at');
}
-----
<?php declare(strict_types=1);

namespace App\RectorTests\Identifier;

use Illuminate\Database\Eloquent\Model;

final class Invoice extends Model
{
    public const AttributeStatus = 'status';
    public const RelationCustomer = 'customer';
    private const AttributeSecret = 'secret';
    public const AttributeUnmapped = 'unmapped';
    public const AttributeRememberToken = 'remember_token';

    protected $fillable = [self::AttributeStatus, self::AttributeUnmapped];
    protected $hidden = [self::AttributeStatus, self::AttributeRememberToken];
    protected $casts = [self::AttributeStatus => 'string'];
}

function query(Invoice $invoice): void
{
    $invoice->where(\App\RectorTests\Identifier\Invoice::AttributeStatus, 'active');
    $invoice->with(\App\RectorTests\Identifier\Invoice::RelationCustomer);
    $invoice->where(\App\RectorTests\Identifier\Invoice::AttributeUnmapped, 'active');
    $invoice->where('secret', 'value');
    $callback = $invoice->where(...);
    $projected = $invoice->newQuery()->selectRaw('MAX(orders.created_at) as last_verification_at', [])->value('last_verification_at');
}
