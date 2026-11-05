# Supported injection points

Require injection where the class or caller supports it. A framework-managed object is not automatically constructor-injectable. The shared registry controls container-resolution diagnostics and method parameter naming.

| Context | Supported injection |
| --- | --- |
| Application `Actions`, `Contracts`, `Services`, `Queries`, `Transformers`, `Builders`, `Factories`, `Managers`, `Resolvers`, `Policies`, `Middleware`, `Middlewares`, and `Listeners` | Constructor; instance methods can reuse those dependencies |
| Endpoint-slice Actions and Queries; standalone, grouped, and conventional Controllers | Constructor; public Controller actions also support method injection |
| Laravel `Illuminate\Routing\Controller` subclasses | Constructor and public action methods, including `__invoke()` |
| Laravel Command subclasses | Constructor and `handle()` |
| Laravel Seeder subclasses | Constructor and `run()` |
| Jobs in `Jobs`, or jobs implementing `ShouldQueue` | `handle()`; constructor arguments are runtime payloads |
| FormRequest subclasses | `rules()`, `authorize()`, `validator()`, and `after()`; other helpers do not receive automatic injection |
| Livewire Components, including Filament Pages and Widgets | `boot()` for protected dependencies on every request; `mount()` for initialization; no constructor injection |
| Named functions | Opt-in only when the caller supplies injected arguments |

Listener `handle()` arguments are event payloads. Inject listener dependencies through the constructor. Queued Job and Listener `failed()` hooks may resolve services because Laravel does not inject these callbacks.

Static helper methods (a `Helpers` namespace segment or a class ending in `Helper`) are exempt from the dependency rule, including date creation. Instance methods and supported injectable callbacks keep their checks. Static Filament Resource methods and their ordinary closures are exempt from the date factory check only. Instance methods and verified injectable callbacks still require a date factory. Prefer injection where available; the exemption does not recommend moving service behavior into static helpers.

Static methods and framework hooks without a supported injection lifecycle are outside this container-resolution check. Livewire instance helpers use dependencies stored by `boot()`; a widget's `getStats()` is called directly and does not support extra injected parameters. Read [Filament and Livewire](filament-and-livewire.md) for callback injection, static Resource boundaries, and UI facade exceptions.

Helpers and closures inside a constructor-injectable class can use its injected dependencies. In method-only contexts, this check covers the registered method and its closures. It does not impose naming on closure parameters or ordinary Action/Query inputs. Function registration affects container-resolution checks; function parameter naming remains outside the naming rule.

FormRequest and JsonResource subclasses may use their own `$this->container->make()` where method injection is unavailable. Resources must provide that property. Registered FormRequest injection methods must receive dependencies through parameters.

## Dynamic container lookups

Runtime selection may resolve a variable class name or binding key, for example `$this->container->make($algSigner)`. Prefer an injected container for this work. The container-resolution rule checks only direct `Type::class` arguments and literal strings that name a known class, within supported injection contexts. It also supports named arguments. Variables remain allowed even when PHPStan can infer a class-string type or a fixed value. Dynamic expressions, non-class binding keys, and container access without a class argument are outside this check.

## Container composition methods

A Factory may own a runtime binding and its scoped lifetime, such as one API client per tenant and role. Resolving that product when requested preserves the container's lifetime behavior. Inject stable collaborators normally.

Register the specific class and method in `larastrict.injectionPoints.containerCompositionMethods`. That method and its inline closures may call `make()` through an injected Application or Container. Other methods retain the normal checks. Global `app()`, `resolve()`, and static container access remain checked. This exception does not disable date, transaction, facade, or direct-construction rules, and does not make arbitrary method parameters injectable.

## Read or extend the registry

Read [InjectionPointRegistry](../../../../vendor/larastrict/conventions/src/PHPStan/InjectionPointRegistry.php) for methods and functions, and [InjectableClassSelector](../../../../vendor/larastrict/conventions/src/PHPStan/InjectableClassSelector.php) for constructor roles and value-type exclusions. Add project-specific support through the named `larastrict.injectionPoints` service; see [configuration](../../../../vendor/larastrict/conventions/docs/phpstan.md#supported-injection-points).

Before adding a framework method, read its caller in the installed framework. Relevant entry points are [FormRequest](../../../../vendor/laravel/framework/src/Illuminate/Foundation/Http/FormRequest.php), [ControllerDispatcher](../../../../vendor/laravel/framework/src/Illuminate/Routing/ControllerDispatcher.php), [Command](../../../../vendor/laravel/framework/src/Illuminate/Console/Command.php), [Bus Dispatcher](../../../../vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php), and [Seeder](../../../../vendor/laravel/framework/src/Illuminate/Database/Seeder.php). Register a method or function only when the caller actually injects its arguments.
