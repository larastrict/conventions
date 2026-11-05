# Filament and Livewire

These classes have framework-owned lifecycles. Do not add constructor dependencies to Livewire Components, Filament Pages, Widgets, or static Resources.

## Livewire dependencies

Inject reusable collaborators in `boot()` and assign protected properties. Livewire calls this hook on each request, so protected dependencies are restored after hydration. Use `mount()` injection for initialization only. Do not store services as public serialized state.

```php
protected CarbonFactory $carbonFactory;

public function boot(CarbonFactory $carbonFactory): void
{
    $this->carbonFactory = $carbonFactory;
}
```

Directly called helpers such as widget `getStats()` use these properties; they do not receive automatic parameter injection.

## Filament Resources and callbacks

Use typed parameters for evaluated callbacks. For example, a formatting callback can receive `Carbon\Factory as CarbonFactory` and pass it to a pure formatting helper. Inspect the installed callback evaluator before assuming that a callback supports injection. Ordinary PHP closures do not acquire this behavior.

The registry recognizes common Action, Column, and Table callback setters in `CallbackInjection`. Container resolution inside these callbacks recommends typed callback parameters. Extend the verified setter list with `additionalCallbackInjection`; this does not make arbitrary closures injectable. Callback parameter naming is not checked by the method-parameter rule.

Static Resource entry points may resolve the needed collaborator at the UI boundary and pass it to private helpers. Prefer application Queries or Services with normal constructor injection for shared data access and business behavior. Do not retain request-specific dependencies in static properties. Static Resource methods and their ordinary closures are exempt from the date factory check because Resources have no Livewire `boot()` injection lifecycle. Instance methods and verified injectable callbacks still require `CarbonFactory`. Filament Pages and Widgets use Livewire `boot()` injection.

Prefer injecting `Filament\FilamentManager $filamentManager` instead of calling `Filament::…` where injection is supported: constructors of application services, Livewire `boot()`, and verified evaluated callbacks. The installed Filament provider binds the manager to the same scoped instance used by the facade. Do not retain it in singletons or static properties. Read tenant and panel state when the operation runs, rather than capturing it during initialization.

Facade use remains valid in static helpers and framework entry points without injection support. A helper that returns an ordinary validation closure may use `Filament::getTenant()` inside that closure. Do not assume the closure's caller injects dependencies. The PHPStan exception permits these cases; it does not make facades the preferred option where injection works.

`Filament\Facades\Filament` and `Filament\Support\Facades\FilamentView` are allowed in Filament Resource and Livewire Component subclasses. Rector preserves these allowed UI calls. Filament may declare a narrower return type on the facade than on `FilamentManager`; for example, `getPanel()` may be declared as `Panel` on the facade and `?Panel` on the manager. Keep the facade at this UI boundary, or handle the nullable manager result explicitly when choosing injection. Application facades such as `DB` and `Cache` retain their checks. Add exact UI facade types through `larastrict.injectionPoints.frameworkUiFacades`; this setting replaces the default list.

A `Filament\PanelProvider` subclass may live directly in the domain's `Filament` folder, such as `app/Filament/FilamentPanelServiceProvider.php`. Ordinary ServiceProvider placement stays unchanged.

## Read the implementation

Read Livewire's [lifecycle hooks](../../../../vendor/livewire/livewire/src/Features/SupportLifecycleHooks/SupportLifecycleHooks.php) and [Wrapped](../../../../vendor/livewire/livewire/src/Wrapped.php) for hook invocation. Read Filament's [EvaluatesClosures](../../../../vendor/filament/support/src/Concerns/EvaluatesClosures.php) and the specific component's callback caller for supported parameters. Read Filament's [service provider](../../../../vendor/filament/filament/src/FilamentServiceProvider.php) and [facade](../../../../vendor/filament/filament/src/Facades/Filament.php) for the scoped manager binding. The [injection registry](../../../../vendor/larastrict/conventions/src/PHPStan/InjectionPointRegistry.php) contains lifecycle methods and exact facade exceptions.
