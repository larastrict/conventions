<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Models\Customer;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Services\SaveService;

/**
 * @extends Factory<Customer>
 */
final class InputModelFactory extends Factory
{
    public function __construct(SaveService $value, string $requiredValue)
    {
        unset($value, $requiredValue);
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [];
    }
}
