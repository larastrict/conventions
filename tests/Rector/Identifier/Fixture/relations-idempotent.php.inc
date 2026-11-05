<?php declare(strict_types=1);

namespace App\RectorTests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

interface HasCustomer
{
    public const RelationCustomer = 'customer';

    public function customer(): HasOne;
}

final class CustomerModel extends Model implements HasCustomer
{
    public function customer(): HasOne
    {
        throw new \LogicException();
    }
}
