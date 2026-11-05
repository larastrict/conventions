<?php declare(strict_types=1);

namespace App\Billing\Factories;

use App\Billing\Actions\ChargeAction;
use Illuminate\Container\Container;

final readonly class DynamicLookupFactory
{
    public function __construct(
        private Container $container,
    ) {}

    public function create(string $algSigner): void
    {
        $this->container->make($algSigner);
        app($algSigner);
        resolve($algSigner);
        Container::getInstance()->make($algSigner);
        $this->container->make('runtime.signer');
        app('runtime.signer');
        resolve('runtime.signer');
        $knownClass = ChargeAction::class;
        $this->container->make($knownClass);
        $this->container->make($algSigner === 'charge' ? ChargeAction::class : \stdClass::class);
        $this->container->make(abstract: $algSigner);
        app();
        $lookup = $this->container->make(...);
        $helper = app(...);

        $this->container->make(ChargeAction::class);
        app(ChargeAction::class);
        resolve(ChargeAction::class);
        $this->container->make('App\Billing\Actions\ChargeAction');
        $this->container->make(abstract: ChargeAction::class);
        app(abstract: ChargeAction::class);
        resolve(name: ChargeAction::class);
        app()
            ->make(ChargeAction::class);
    }
}
