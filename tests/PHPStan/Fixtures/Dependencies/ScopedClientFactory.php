<?php declare(strict_types=1);

namespace App\Billing\Factories;

use App\Billing\Actions\ChargeAction;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use stdClass;

final readonly class ScopedClientFactory
{
    public function __construct(
        private Container $container,
    ) {}

    public function createScoped(string $key): object
    {
        if ($this->container->bound($key) === false) {
            $this->container->scoped($key, static function (ContainerContract $container): object {
                return $container->make(stdClass::class);
            });
        }

        return $this->container->make($key);
    }

    public function unrelated(): object
    {
        return $this->container->make(stdClass::class);
    }

    public function createUnsafe(): void
    {
        app(stdClass::class);
        resolve(stdClass::class);
        Container::getInstance()->make(stdClass::class);
        now();
        new ChargeAction();
    }
}
