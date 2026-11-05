<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Models\Good;

final class IdentifierUsage
{
    public function read(Order $order): string
    {
        self::consume($order->where(Order::AttributeStatus, 'new'));
        self::consume($order->with(Order::RelationCustomer));
        self::consume((new UnrelatedRecord())->where('status'));
        self::consume((new UnrelatedRecord())->with('customer'));
        self::consume($order->where('orders.status'));

        return $order->status;
    }

    public function readProjectedValue(Order $order): mixed
    {
        $table = 'orders';

        return $order->newQuery()
            ->selectRaw("MAX({$table}.created_at) as last_verification_at", ['ignored'])
            ->value('last_verification_at');
    }

    private static function consume(mixed $value): void
    {
    }
}
