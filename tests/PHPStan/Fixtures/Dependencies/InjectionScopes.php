<?php declare(strict_types=1);

namespace App\Probe\Widgets;

use App\Billing\Actions\ChargeAction;
use Illuminate\Container\Container;

final class OverviewWidget
{
    private function getStats(): array
    {
        app(ChargeAction::class);
        resolve(ChargeAction::class);
        Container::getInstance()->make(ChargeAction::class);

        return [];
    }
}

namespace App\Probe\Jobs;

final class SyncJob
{
    public function __construct()
    {
        app(\stdClass::class);
    }

    public function handle(): void
    {
        app(\stdClass::class);
        $callback = static fn () => resolve(\stdClass::class);
    }

    private function helper(): void
    {
        app(\stdClass::class);
    }
}

namespace App\Probe\Services;

final class ScopedService
{
    private function helper(): void
    {
        app(\stdClass::class);
        $callback = static fn () => resolve(\stdClass::class);
    }

    public static function staticHelper(): void
    {
        app(\stdClass::class);
    }
}

namespace App\Probe\Http\TenantApi\Store;

final class StoreAction
{
    public function execute(): void
    {
        app(\stdClass::class);
    }
}

final class StoreController
{
    public function __invoke(): void
    {
        resolve(\stdClass::class);
    }
}

namespace App\Probe\Framework;

abstract class Handler
{
}

final class CustomHandler extends Handler
{
    public function process(): void
    {
        app(\stdClass::class);
    }

    public function ignored(): void
    {
        app(\stdClass::class);
    }
}

namespace App\Probe\Workflows;

final class ExecuteWorkflow
{
    public function handle(): void
    {
        app(\stdClass::class);
    }

    public function ignored(): void
    {
        app(\stdClass::class);
    }
}

namespace App\Probe;

function runWithDependencies(): void
{
    app(\stdClass::class);
}

function plainFunction(): void
{
    app(\stdClass::class);
}

namespace App\Probe\EntryPoints;
use App\Billing\Actions\ChargeAction;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Controller;

final class ScopeCommand extends Command
{
    public function handle(): void
    {
        app(\stdClass::class);
    }

    private function helper(): void
    {
        resolve(\stdClass::class);
    }
}

final class ScopeSeeder extends Seeder
{
    public function run(): void
    {
        app(\stdClass::class);
    }
}

final class ScopeController extends Controller
{
    public function index(): void
    {
        app(\stdClass::class);
    }
}

final class ScopeRequest extends FormRequest
{
    protected function validator(): mixed
    {
        $this->container->make(ChargeAction::class);

        return null;
    }

    protected function after(): array
    {
        resolve(\stdClass::class);

        return [];
    }

    protected function prepareForValidation(): void
    {
        app(\stdClass::class);
    }
}
