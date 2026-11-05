<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Models\Good;

interface OrderCapability
{
    public const RelationCustomer = 'customer';
    public const AttributeStatus = 'status';
}
