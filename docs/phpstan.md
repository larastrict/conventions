# PHPStan rules

[Back to README](../README.md)

The default extension enables all LaraStrict rules. Existing applications can
report new findings after a package update.

## Enable or disable a rule

Change a named service in the application's `phpstan.neon`:

```neon
services:
    larastrict.classPlacement:
        arguments:
            enabled: false
```

Set `enabled: true` to enable the rule again.

| Service | What it checks |
| --- | --- |
| `larastrict.classPlacement` | Class names, file names, and namespace placement |
| `larastrict.parameterName` | Injected collaborator names at dependency injection points |
| `larastrict.actionQueryPublicApi` | Public `execute()` methods on Actions and Queries |
| `larastrict.dependencies` | Dependency injection, date creation, and transactions |
| `larastrict.viewData` | Typed Entity data passed to application views |
| `larastrict.viewTemplate` | Typed data declarations and access in Blade templates |
| `larastrict.modelIdentifiers` | Model column keys, relation keys, and relation method names |
| `larastrict.requestKeys` | Request field keys in validation and input calls |
| `larastrict.injectable` | Whether the application container can create classes and resolve contracts |

See [extension.neon](../extension.neon) for the default settings.
Use the default extension and named service settings for all rules.

## Package placement extensions

Packages can add placement allowances with `ClassPlacementExtension`. Register
one service per extension in the package's PHPStan NEON file:

```neon
services:
    -
        class: Vendor\Sdk\PHPStan\SdkPlacementExtension
        tags: [larastrict.classPlacementExtension]
```

```php
namespace Vendor\Sdk\PHPStan;

use LaraStrict\Conventions\PHPStan\ClassPlacementExtension;

final class SdkPlacementExtension implements ClassPlacementExtension
{
    public function getAllowedNamespacePaths(string $suffix): array
    {
        return $suffix === 'Entity' ? [['Sdk', 'Endpoints', '*']] : [];
    }
}
```

Include the package's NEON file in the application's PHPStan configuration, or
register it through `phpstan/extension-installer` with Composer's
`extra.phpstan.includes`. Services from multiple packages are collected together.
The example allows `Orders/Sdk/Endpoints/Devices/DeviceEntity.php`.
Each path matches consecutive namespace segments at any depth. `*` requires one
segment; nested endpoint namespaces also match. Paths must not be empty.

Extensions add allowed locations for suffixes configured in `namespaceBySuffix`.
They preserve the default locations, filename checks, and namespace path checks.
They do not require other classes in the added location to use that suffix.
Package paths appear in placement diagnostics. Rules other than class placement
keep their existing configuration settings.

## Supported injection points

The shared `larastrict.injectionPoints` registry defines supported constructors,
framework injection methods, and opt-in named functions. See the skill's
[supported injection points](../resources/ai/larastrict/references/injection-points.md)
for the default list and framework source links.

Container-resolution diagnostics require a supported injection path. A namespace
under `App` alone is not sufficient. For example, a Filament widget's `getStats()`
does not receive constructor or method injection and is outside this check.
Helpers of constructor-injectable classes can use their existing dependencies;
method-only classes are checked in registered methods and their closures.

The binding argument must directly name a class: `Type::class` or a literal
string recognized as a class. Runtime variables such as `$algSigner` remain
allowed, including inferred class-strings and variables assigned a class
constant. Dynamic expressions, non-class binding keys, and zero-argument
container access are not reported. This applies to `make()`, `app()`, and
`resolve()`, including named arguments. For example:

```php
$this->container->make($algSigner); // Runtime selection: allowed.
$this->container->make(Sha256::class); // Direct class: use injection.
```

Extend the shared list in the application's PHPStan configuration:

```neon
services:
    larastrict.injectionPoints:
        arguments:
            additionalConstructorNamespaces: [\Repositories\]
            additionalConstructorBaseClasses: [App\Framework\ContainerCreatedHandler]
            additionalMethodInjection:
                App\Framework\Handler: [process]
            additionalNamespaceMethodInjection:
                \Workflows\: [handle]
            functions: [App\Workflows\runWithDependencies]
```

The `additional*` settings preserve defaults. Method maps match subclasses and
implemented interfaces; method names are case-insensitive. `*` selects public
instance action methods, including `__invoke()`. Static methods are excluded.
Register a callable only when its caller supplies dependencies. Registration
does not change runtime behavior. Named function registration scopes container
diagnostics; parameter naming still checks class injection methods only.

`constructorNamespaces` and `constructorBaseClasses` can replace constructor
defaults. The runtime container-construction rule has separate `namespaces` and
`extends` settings; keep them aligned when adding constructor roles.

### Container composition methods

A Factory that registers and resolves runtime scoped bindings can be an explicit
container composition boundary. Register its class and method:

```neon
services:
    larastrict.injectionPoints:
        arguments:
            containerCompositionMethods:
                App\Orders\Factories\OrdersApiFactory: [createIntegration]
```

Only the named methods and their inline closures may resolve objects through an
injected container instance. Global helpers and static container access remain
checked. Other Factory methods, parameter naming, and the other dependency rules
are unchanged. Method names are case-insensitive; entries also match subclasses
and implementations of the configured type. This list is empty by default.

### Filament and Livewire

Livewire Components and Filament Resources are excluded from constructor
selection, including the runtime container-construction check. Livewire `boot()`
and `mount()` are registered method injection points. Instance helpers must use
dependencies assigned in `boot()`; these diagnostics recommend that lifecycle
instead of constructor injection. Date and application-facade checks remain
enabled and give UI-specific guidance.

Common evaluated Action, Column, and Table callbacks are recognized through
`InjectionPointRegistry::CallbackInjection`. Container calls inside them must
use typed callback injection for direct class dependencies. Add verified callback
setters through `additionalCallbackInjection` (base type to method-name list).
Callback parameter naming remains outside the method-parameter rule.

The exact `Filament\Facades\Filament` and
`Filament\Support\Facades\FilamentView` facade types are allowed only in Resource
and Livewire Component subclasses. The registry's `frameworkUiFacades` setting
can replace this list. Prefer injecting `Filament\FilamentManager` where the
lifecycle supports injection. Facades remain valid for static helpers and framework
entry points without injection support. The manager uses the facade's scoped
binding; do not retain it in singletons. Other application classes retain their
facade checks.

Subclasses of `Filament\PanelProvider` may live directly under `Filament` even
when the provider name differs from the folder name. Other providers retain
normal placement. See the [UI skill reference](../resources/ai/larastrict/references/filament-and-livewire.md).

### Global and subdomain providers

Laravel ServiceProvider subclasses may live directly under the application's
`Providers` namespace (`App\Providers` by default). Domain providers stay at their
domain root; nested domain `Providers` folders are not included in this allowance.

A subdomain provider may use `{SingularSubdomain}{SingularParentDomain}ServiceProvider`,
using its immediate parent: `Methods\Certificate\CertificateMethodServiceProvider`
and `Payments\Invoices\InvoicePaymentServiceProvider`. Existing
`{Domain}ServiceProvider` names remain valid. Role folders are not subdomains;
capability providers retain their capability-root naming.

## Application namespaces

Static rules check the `App` namespace by default. Set `applicationNamespaces`
on each rule that needs another namespace. For example:

```neon
services:
    larastrict.classPlacement:
        arguments:
            applicationNamespaces: [Company\Application]
```

The placement rule also accepts `excludedClasses`, `excludedNamespaces`, and
`namespaceBySuffix`. The parameter-name rule preserves required inherited method
signatures and skips types it cannot identify safely.

Entities use the `Entity` suffix. Prefer semantic parameter names such as `$order`;
the parameter-name rule does not check DTOs or Models.
Converters in `Transformers` may use `SourceToTarget` names, such as
`RequestToOrderEntity`, without a `Transformer` suffix. The target's `Entity`
suffix does not require the converter to be placed in `Entities`.
Eloquent models use the `Models` folder. Concrete models must not use a `Model`
suffix. Abstract models may use it, such as `TenantAwareModel`.
Model traits and interfaces can share a `Models/HasName` folder. All application
Eloquent scopes must live under `Models`, directly or in subfolders such as
`Models/Scopes` and `Models/HasName`, and their names must end with `Scope`.
This includes abstract scopes and classes that implement
`Illuminate\Database\Eloquent\Scope` through a shared base. Use
`WhereStateInScope` instead of `WhereStateIn`. Classes named `*Scope` also require
a `Models` namespace. A domain-level `Scopes` folder is not an allowed scope location. Other Model classes keep their own names.

Eloquent model factories may live beside their Model in `Models`, such as
`Models/OrderFactory.php`. The exception checks inheritance from
`Illuminate\Database\Eloquent\Factories\Factory`, including shared base factories.
Other application factories still require `Factories`.

## HTTP placement

Controllers, Requests, and Resources use `Http/Controllers`, `Http/Requests`, and
`Http/Resources`. Alternatively, group an endpoint's Controller, Request, Resource,
Entity, Action, and Query in `Http/{area}/{endpoint}`, such as
`Http/TenantApi/Login/LoginUserEntity.php`. Version segments may add depth.
Each file must match its class name and namespace. Shared domain DTOs stay in
`Entities`.

The skill calls this layout an **endpoint slice**: a vertical slice within a
domain for one HTTP use case. JetBrains HTTP Client `.http` files may live beside
the Controller. These request examples do not affect PHP class placement.

A standalone Controller may also live directly in `Http/{area}`, such as
`Http/TenantWeb/ShowOrderController.php`. No paired Request or Resource
is required. Its constructor and public method injection remain checked by the
parameter-name and container rules.

Endpoint Actions and Queries keep their `execute()` API, injection naming, and
container checks. Their placement exception requires both area and endpoint;
`Http/{area}` alone is allowed only for Controllers. Services keep their own
folders. The exception does not allow Entities inside
`Http/Controllers`, `Http/Requests`, or `Http/Resources`.
Resource placement checks HTTP serializers; other resource kinds, such as
Filament panel resources, keep their framework layout.

## Parameter names

The rule checks injected collaborators at these application injection points:

- Constructors and promoted properties of injectable Actions, Queries, Services,
  application Factories, Transformers, Builders, Managers, Resolvers, Policies,
  Middleware, Listeners, and Controllers.
- Public Controller methods, including grouped HTTP Controllers and `__invoke()`.
- Command and Job `handle()` methods, and Seeder `run()` methods.
- FormRequest `rules()`, `authorize()`, `validator()`, and `after()` methods.

Ordinary methods, Action/Query `execute()` inputs, free functions, and closures
are not injection points. Listener event arguments and Job constructor payloads
also keep semantic names. Requests, Resources, Eloquent model factories, Models,
Entities, enums, and runtime query builders are not injectable declaring classes.
Their input and conversion parameters keep semantic names: `GetValue $data`,
`GetValue $body`, and `GetValue $payload` are all valid.

The declaring class must be in `applicationNamespaces`. Constructor eligibility
uses the shared registry and the same class selector as the container rule.
`injectableNamespaces` and `injectableBaseClasses` remain rule-local constructor
overrides. Prefer the registry to keep naming and resolution scopes aligned.

The collaborator type must belong to an application namespace or
`componentNamespaces` (default: `LaraStrict`) or additive
`additionalComponentNamespaces` supplied by a package and have a role selected through
`componentNamespaceSegments`. Contracts and Interfaces count as collaborator
boundaries. Value types and Eloquent factories are excluded. `Carbon\Factory`
is an explicit collaborator. External interfaces such as `Lcobucci\JWT\Builder`
keep semantic names unless their owner and role are configured for checking.

Names use the short class name or import alias, without a trailing `Contract` or
`Interface`. Role prefixes and required inherited parameter names are preserved.
If that leaves only `Factory`, `Service`, `Builder`, `Action`, `Query`, or
`Transformer`, prefix it with the immediately preceding namespace segment:
`Carbon\Factory` uses `$carbonFactory`, and `Payment\ServiceInterface` uses
`$paymentService`. This also applies to fully qualified types. Descriptive import
aliases keep their names.
For example, use `PaymentServiceInterface $paymentService` or
`RequestToOrderEntity $requestToOrderEntity`. `excludedTypes` can skip a
collaborator type completely:

```neon
services:
    larastrict.parameterName:
        arguments:
            excludedTypes:
                - App\Orders\Services\ExternalPaymentService
```

## Actions, Queries, and dependencies

Actions and Queries expose `execute()`. Methods required by external interfaces
are also allowed. Query return types are unrestricted; PHPStan still checks the
declared types.

Queries may call Services, Queries, Actions, Factories, Transformers, and other
collaborators. There is no dependency direction restriction based on these roles.
By design, prefer prepared values passed through Entities or method parameters.
Simple data conversion may stay in the Query when that keeps the operation clear.
For retried transactions, generate values inside the retry boundary when each
attempt needs a new value; generate them outside when attempts must share a value.
Calls inside that boundary may run more than once and must be safe to repeat.
Keep Queries focused on persistence. Put business workflow orchestration in
Actions or focused endpoint Controllers.
Inject collaborators through the constructor.

The dependency rule checks direct construction of application services, container
lookups, date creation, and transactions. New value Entities are allowed.
Static methods in a `Helpers` namespace segment or a class ending in `Helper`
are exempt from this rule, including date creation. Static Filament Resource
methods and their ordinary closures are exempt from the date factory check only.
Resources have no Livewire `boot()` injection lifecycle. Instance methods and
verified injectable callbacks still require a date factory. Filament Pages and
Widgets use Livewire `boot()` injection. Other dependency checks keep their scope.

Providers can configure the container. Use `dependencyConstructionAllowlist` for
classes that must be created directly.

Container resolution is allowed in `failed()` on jobs and listeners that implement
`Illuminate\Contracts\Queue\ShouldQueue`. Laravel calls these methods without
method injection. Constructor-injectable listeners and registered Job methods
still require injection outside that hook.

FormRequest and JsonResource subclasses may resolve services through
`$this->container->make()`. This includes resource collections and subclasses of
application base classes. FormRequest provides the container. A resource must
provide its own container property. Registered FormRequest methods (`rules()`,
`authorize()`, `validator()`, and `after()` by default) must use method injection;
container resolution is prohibited in these methods.

These container-resolution rules apply to `larastrict.dependencies.containerResolution`.
Date, transaction, facade, and direct-construction checks keep their existing
application scope.

## Views

The view rule checks literal names such as `Orders::show` passed to `view()` or
Laravel's view Factory, including named arguments. View data must contain a
non-null `*Entity` object from `Entities` or an HTTP endpoint namespace under the
`data` key.
Other values must be scalar.

Use `excludedViewNamespaces` to exclude framework, vendor, or explicitly excluded application
views. Dynamic view names and container calls still need review.
See [Blade checks](ai-agents.md#blade-checks) for raw Blade template checks. The
`ViewTemplateRule` runs as part of PHPStan and compiles Blade files before it
checks them. Run PHPStan with existing application view directories, for example:

```bash
vendor/bin/phpstan analyse app/Order/Views resources/views
```

## Requests

The request rule checks literal field keys returned directly from `rules()` and
keys passed to FormRequest or GetValue input methods. It supports named arguments,
field paths, and arrays of paths. Validation rule names are excluded.
Computed keys and validation built in other methods still need review.

## Models

The model rule checks supported Eloquent Model and Builder calls with simple
literal identifiers. It also checks model property arrays, relation constants,
and snake_case relation methods. Constants can come from capability interfaces.

Relation definitions are public, non-static methods whose return type is an
Eloquent `Relation`. This includes methods supplied by traits. Cast entries and
PHPDoc properties do not define relations. Trait relation diagnostics point to
the Model's `use` statement, where the capability enters that Model.

Qualified columns, SQL expressions, raw SQL, migrations, and dynamic identifiers
still need review. Ordinary property access is allowed.

## Container checks

The container rule is enabled by default. It creates configured application
classes through the real container. Load the application and its service provider
bindings before running PHPStan.

Disable the rule when the application cannot be loaded:

```neon
services:
    larastrict.injectable:
        arguments:
            enabled: false
```

You can also change its scope:

```neon
services:
    larastrict.injectable:
        arguments:
            enabled: true
            extends:
                - Illuminate\Console\Command
                - Illuminate\Database\Seeder
            namespaces:
                - \Actions\
                - \Services\
            excludeSuffixes:
                - Interface
                - Expectation
                - Assert
            excludeFolders:
                - tests
```

Namespace entries match parts of a class namespace. The `extends` setting also
selects classes outside those namespaces. The rule also accepts
`appendNamespaces`, `excludeNamespaces`, and `excludeClasses`.
