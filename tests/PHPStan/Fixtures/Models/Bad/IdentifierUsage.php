<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Models\Bad;

use Illuminate\Database\Query\Expression;

final class IdentifierUsage
{
    public function read(Invoice $invoice): bool
    {
        self::consume($invoice->where('number'));
        self::consume(Invoice::with('customer'));
        self::consume(Invoice::query()->where('owner_id'));
        self::consume($invoice->with('missingRelation'));
        self::consume($invoice->with('lines'));
        self::consume($invoice->where('orders.number'));
        self::consume($invoice->where(new Expression('number')));

        return $invoice->exists;
    }

    private static function consume(mixed $value): void
    {
    }
}
