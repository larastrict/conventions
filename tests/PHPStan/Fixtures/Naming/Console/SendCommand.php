<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Console;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Services\SaveService;

final class SendCommand extends \Illuminate\Console\Command
{
    public function handle(SaveService $service): void
    {
        unset($service);
    }
}
