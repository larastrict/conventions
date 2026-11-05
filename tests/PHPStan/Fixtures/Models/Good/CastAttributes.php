<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Models\Good;

use Illuminate\Database\Eloquent\Model;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Models\Support\Customer;

/**
 * @property array<string, mixed> $verification
 * @property Customer $customer
 */
final class CastAttributes extends Model
{
    public const string AttributeVerification = 'verification';

    protected $casts = [
        self::AttributeVerification => 'array',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            self::AttributeVerification => 'array',
        ];
    }
}
