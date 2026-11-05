<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Seeders;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Services\SaveService;

final class SendSeeder extends \Illuminate\Database\Seeder
{
    public function run(SaveService $service): void
    {
        unset($service);
    }
}
