# Domain architecture

## Domain layout and names

Organize application code by domain. Match the namespace and filename to the class. Add folders only when the domain needs them.

| Type or required suffix | Folder | Example |
| --- | --- | --- |
| `Action` | `Actions` or an endpoint slice | `CreateOrderAction` |
| `Service` | `Services` | `PaymentService` |
| `Factory` | `Factories` | `PaymentServiceFactory` |
| Eloquent `*Factory` | `Models`, beside its Model | `Models/OrderFactory` |
| `Entity` | `Entities` or an endpoint slice | `OrderEntity`, `Http/TenantApi/Login/LoginUserEntity` |
| Eloquent model; never add `Model` | `Models` | `Order` |
| `Contract` | `Contracts` | `SendOrderActionContract` |
| `Interface` | `Interfaces` | `PaymentServiceInterface` |
| `Query` | `Queries` or an endpoint slice | `GetOrderQuery` |
| `Builder` | `Queries/Builders` | `OrderBuilder` |
| `Scope` | `Models` or a subfolder such as `Models/Scopes` or `Models/HasX` | `WhereStateInScope`, `ActiveOrderScope` |
| `Transformer` or `SourceToTarget` | `Transformers` | `OrderTransformer`, `RequestToOrderEntity` |
| `Controller` | `Http/Controllers`, `Http/{area}`, or an endpoint slice | `LoginUserController` |
| `Request` | `Http/Requests` or an endpoint slice | `LoginUserRequest` |
| `Resource` (HTTP) | `Http/Resources` or an endpoint slice | `LoginUserResource` |
| `ServiceProvider` | Domain/subdomain root; global Laravel providers in `App\Providers` | `OrderServiceProvider`, `CertificateMethodServiceProvider` |

Model capability interfaces stay with their traits in `Models/HasX` or `Models/Interfaces`. See [models](models.md). Follow the consumer's configured placement for non-query builders. Keep domain ownership explicit; move code to a shared domain when actual reuse requires it.

Place an Eloquent model Factory beside its Model, for example `Models/Order.php` and `Models/OrderFactory.php`. This exception applies to subclasses of Laravel's Eloquent Factory, including inherited factories. Other application Factories stay in `Factories`.

An **endpoint slice** is a small vertical slice within a domain, stored in `Http/{area}/{endpoint}`, for example `Http/TenantApi/Login`. Keep the Controller and its endpoint-owned Request, Resource, Entity, Action, and Query together. Add only the files the endpoint needs. Keep shared behavior in the domain's role folders. Endpoint Actions and Queries retain constructor injection and public `execute()`. See [HTTP layouts](http-and-views.md#http-layouts) for scope and optional JetBrains `.http` files. Shared domain Entities stay in `Entities`; Services keep their own folder.

A standalone Controller may live directly in `Http/{area}`, such as `Http/TenantWeb/ShowOrderController.php`. It does not need a paired Request, Resource, or endpoint folder.

A `Filament\PanelProvider` subclass may live directly under `Filament`, such as `Filament/FilamentPanelServiceProvider.php`. See [Filament and Livewire](filament-and-livewire.md) for UI dependency injection and facade exceptions.

## Capabilities within a domain

A capability is a small, cohesive module owned by a domain. Place it in `app/{Domain}/Capabilities/{Capability}`, for example `app/Core/Capabilities/LazyPaginator`. Keep small capabilities flat. Add subfolders only when needed.

```text
app/Core/Capabilities/LazyPaginator/
    LazyPaginator.php
    PaginationCursor.php
    LazyPaginatorFactory.php
    LazyPaginatorServiceProvider.php
```

Capability types may use descriptive names without a role suffix, such as `LazyPaginator` and `PaginationCursor`. Named components such as `*Action`, `*Query`, `*Service`, and `*Factory` may live directly in the capability. Their responsibilities and injection rules still apply. Flat Actions and Queries keep public `execute()` as their only application operation. Ordinary technical types are not automatically container services.

Keep filenames and namespaces aligned. Eloquent Models must not end in `Model`; Eloquent Scopes still end in `Scope` and live under `Models`. Add a provider only when registration is needed. Name it `{Capability}ServiceProvider` and place it at the capability root. Register it through the application's existing provider setup; a capability folder does not imply automatic discovery.

A capability may depend on its owning domain and shared technical code. Shared `Core` capabilities must not depend on business domains. Expose a small public API for consumers. These ownership boundaries are design conventions; the placement allowance does not enforce dependency direction. Keep capability tests together under `tests/{Domain}/Capabilities/{Capability}`. Call a module a package when it has its own Composer boundary.

## Contracts and Interfaces

Use `Contracts` for directly injected behavior with one active binding. For example, bind `SendOrderActionContract` to `SendOrderAction` in the domain provider, then inject the Contract into callers. Introduce a Query Contract when a consumer needs a typed test boundary. Generate its test helpers; see [testing](../../larastrict-testing/SKILL.md).

Use `Interfaces` for a family of implementations selected at runtime, such as `PaymentServiceInterface` implementations chosen by `PaymentServiceFactory` from an enum. Tag the implementations in providers and inject the list into the Factory; see [providers](providers.md). Callers inject the Factory instead of choosing a concrete strategy.

Model capability interfaces describe behavior and identifiers shared by Models. They do not need container bindings or a `Contract` suffix.

## Responsibilities and dependencies

Use a named Action for business workflows that need a separate application boundary. A focused workflow used by one endpoint may remain in its controller.

Controllers may implement a focused, endpoint-specific use case. They may check eligibility, call Queries, select behavior through Factories, and coordinate application collaborators.

Extract an Action when the workflow is reused, needs an independent application boundary, or becomes difficult to understand or test in the controller. Do not require an Action solely because a controller contains business conditions or calls multiple collaborators. Keep persistence implementation in Queries.

An `Action` executes one business use case. Its only application operation method is public `execute()`; its constructor and methods required by external interfaces are allowed. Inject collaborators through the constructor. Prefer a result Entity when the use case produces useful structured data; use `void` when it has no result. New value Entities may be constructed directly.

A `Query` owns a focused persistence operation and exposes public `execute()` only, apart from its constructor and methods required by external interfaces. It may read or write data. Keep use-case orchestration in an Action or a focused endpoint Controller; a Query may perform multiple SQL statements for one cohesive persistence operation. Inject collaborators through the constructor. Query return types are unrestricted: retain ordinary PHPStan types, including scalars, arrays, iterables, and Entities. Prefer Entity inputs; a Model may be appropriate for model event flows. Prefer an Entity when it gives callers a useful typed result. Reuse constraints through Scopes and shared query construction through an abstract Query or an internal Builder in `Queries/Builders`.

Before creating a Query, search the domain's shared Queries and controller or endpoint slices for an existing operation that fits. Reuse it when its persistence behavior and inputs match. If a suitable Query is scoped to one controller or endpoint but another area needs it, move it to the owning domain's shared `Queries` namespace and update its callers, imports, and tests. Keep its behavior and model-specific base intact. Generalize inputs only when the callers share the same persistence operation; keep endpoint-specific validation and workflow orchestration in their callers. Create a new Query when no existing operation fits. See [HTTP layouts](http-and-views.md#http-layouts) for moving shared slice behavior.

For Eloquent persistence, concrete Queries must extend the model's `Abstract{Model}Query`, including write Queries and Queries in endpoint slices. Reuse an existing base. If none exists, create it in the model's domain `Queries` namespace. The base extends `LaraStrict\Database\Queries\AbstractEloquentQuery` with `@extends AbstractEloquentQuery<Model>` and implements protected `getModelClass(): string` to return `Model::class`. Choose the base for the model being queried, even when the concrete Query belongs to another domain or an endpoint slice.

Use inherited helpers or `$this->getQuery()` to build operations for that model instead of starting a separate `Model::query()` chain. Reuse constraints through Scopes. An internal Builder may support complex construction but does not replace the model's abstract Query base. This inheritance rule applies to Eloquent Queries; Queries for raw SQL or other persistence backends do not need an Eloquent base.

A Query may call Services, Queries, Actions, Factories, Transformers, and other collaborators as part of its persistence operation. A collaborator's role or suffix does not prohibit the dependency. By default, prefer prepared values passed through an Entity or method parameters over calls to collaborators from the Query. Simple data conversion inside a Query may be preferable when it keeps the persistence operation clear and avoids unnecessary preparation in callers.

Choose where to generate values based on transaction retry behavior. If a key, ID, or other value must be generated again for each attempt, prefer calling its generation Action inside the retried operation. If a value must stay the same across attempts, generate it before the retry boundary and pass it in. A collaborator inside a retried operation may run more than once; external effects must be safe to repeat or kept outside that boundary. Keep business workflow orchestration in Actions or focused endpoint Controllers and inject Query collaborators through the constructor.

A reusable `Service` provides shared behavior and may have multiple methods. Use an Action when a business operation needs a separate application boundary. A `Factory` selects or builds an implementation; keep selection policy out of controllers and callers. Inject collaborators into application classes, including Actions, Queries, Services, and Factories.

Prefer direct concrete injection when there is one clear implementation. Add a Contract when callers need a stable behavior boundary. Use a Factory for runtime selection, such as an enum-selected strategy; inject the implementations and select from them. A Factory that owns runtime container bindings and their scoped lifetime may resolve its products through an injected container. Register that specific [container composition method](injection-points.md#container-composition-methods); ordinary Factory methods keep constructor injection.

## DTO (Entities and conversion)

Use immutable `*Entity` DTOs for stable input and results. Use mutable `*StateEntity` DTOs for evolving workflow state. Read [DTOs and Transformers](dto.md) for the choice, naming, and conversion boundaries.

## Names and injected parameters

Apply parameter naming to injected collaborators at the [supported injection points](injection-points.md): constructors and promoted properties of injectable application components, public Controller actions, Command and Job `handle()`, Seeder `run()`, and FormRequest `rules()`, `authorize()`, `validator()`, and `after()`. Grouped HTTP Controllers count too. Ordinary helpers and Action/Query `execute()` inputs are not injection points.

For injected application and Larastrict components, use the full short class name or import alias in camel case. Remove a trailing `Contract` or `Interface`: `SendOrderActionContract $sendOrderAction` and `PaymentServiceInterface $paymentService`. A converter's target suffix stays: `RequestToOrderEntity $requestToOrderEntity`. Role prefixes such as `$previousPaymentService` are allowed. Preserve required inherited parameter names.

If the remaining name is exactly `Factory`, `Service`, `Builder`, `Action`, `Query`, or `Transformer`, prefix it with the namespace segment immediately before the class. Examples: `Carbon\Factory $carbonFactory`, `Payment\ServiceInterface $paymentService`, and `Report\BuilderContract $reportBuilder`. This works for imports and fully qualified types. A descriptive import alias takes priority, such as `use Carbon\Factory as CarbonFactory`. The rule still applies only to checked collaborators at injection points.

External interfaces and input values use semantic names. Models, Entities, enums, Eloquent factories, and runtime query builders are not injected collaborators. `GetValue $data`, `$body`, or `$payload` are all valid input names. Do not name every object after its type. `excludedTypes` in `larastrict.parameterName` can exclude a collaborator type from checking.

Inject `Carbon\Factory` as `CarbonFactory` when application logic needs the current time, parsing, or date construction. Do not call `now()`, static Carbon date constructors, or `new Carbon` in application logic. Use injection instead of resolving direct class arguments through the container in supported classes and methods. Runtime-selected classes and binding keys may be resolved through an injected container, such as `$this->container->make($algSigner)`. Livewire helpers use dependencies assigned in `boot()`; static Filament Resource boundaries may resolve dependencies and pass them to helpers. Static Resource methods and their ordinary closures are exempt from the date factory check; instance methods and verified injectable callbacks still require a date factory. Container wiring in providers is allowed. Constructing a new value Entity is allowed.

Laravel lifecycle exceptions allow container resolution in `failed()` on jobs and listeners that implement `Illuminate\Contracts\Queue\ShouldQueue`. FormRequest and JsonResource subclasses may use `$this->container->make()` where method injection is unavailable; a resource must provide its own container property. Registered FormRequest injection methods must use method injection. Date, facade, transaction, and direct-construction checks retain their separate application scope.

## Models and shared keys

A Model is primarily a persistence object. Prefer a typed Entity as general inter-layer data when it gives callers a clearer contract; Query result types remain unrestricted, so a Model may be the useful result. See [model conventions](models.md) for attribute constants and the `HasObject` pattern.

Use constants for repeated external string keys when they have a clear owner. Do not introduce a constant for every string literal.

## Transactions

Use the installed `LaraStrict\Database\Contracts\RunInTransactionActionContract` for application transactions. Inject it into the Action or focused endpoint Controller coordinating the use case. Verify the exact namespace in the installed package.

For Contexts, memory, or repository caching, use [larastrict-cache](../../larastrict-cache/SKILL.md).

## In-memory data

For ordinary in-memory mapping, prefer arrays and one `foreach` over Laravel Collections. Combine filtering and transformation in the fewest clear passes. Eloquent query chains and database retrieval methods such as `pluck()` are allowed. Use a Generator when a caller benefits from incremental streaming. Do not collect a streamed result if it can be processed item by item.

## Read the implementation

Read [AbstractQuery](../../../../vendor/wrkflow/larastrict/src/Database/Queries/AbstractQuery.php) and [AbstractEloquentQuery](../../../../vendor/wrkflow/larastrict/src/Database/Queries/AbstractEloquentQuery.php) before extending an Eloquent Query and [RunInTransactionActionContract](../../../../vendor/wrkflow/larastrict/src/Database/Contracts/RunInTransactionActionContract.php) before wiring a transaction. Source links resolve from the installed application skill.
