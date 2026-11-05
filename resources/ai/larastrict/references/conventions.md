# Laravel implementation conventions

These rules extend the shared PHP conventions for Laravel work. Laravel rules take precedence where they differ from the shared PHP principles.

## MUST USE

- Use a named `Action` for business workflows that need a separate application boundary. A focused workflow used by one endpoint may remain in its controller. An Action's public operation is `execute()`; return a result Entity when useful, otherwise `void`. Actions may construct new value Entities. Inject collaborators through the constructor.
- MUST USE a focused `Query` for persistence work. Its public operation is `execute()`; ordinary PHPStan return types are unrestricted. Queries may call Services, Queries, Actions, Factories, Transformers, and other collaborators. Prefer prepared values passed through Entities or parameters. Simple conversion may stay in the Query. Place value generation inside or outside the transaction retry boundary according to whether each attempt needs a new value or the same value. See the domain reference. Keep workflow orchestration in Actions or focused endpoint Controllers. Inject collaborators through the constructor.
- MUST USE the model's `Abstract{Model}Query` base for Eloquent Queries, including writes and endpoint slices. Reuse the existing base or create it in the model's domain `Queries` namespace. Extend `LaraStrict\Database\Queries\AbstractEloquentQuery<Model>`, implement protected `getModelClass()`, and use inherited helpers or `$this->getQuery()` for the model. See the domain reference.
- MUST USE a `Service` for shared application behavior. Technical types in a capability module may keep descriptive names; see the domain reference. Use a `Factory` for runtime implementation selection or a named construction policy; inject a concrete implementation directly when no selection is needed. Inject collaborators into these application components. Register container composition methods when a Factory owns runtime scoped bindings. For framework-managed objects, use the supported injection points.
- MUST USE typed request input. Direct getters may cover up to four input properties; use an Entity for larger input sets. Views receive typed Entities as `['data' => $entity]`; component Blade files use `@props` before the PHPDoc. See the HTTP and view reference for the distinct normal-view form.
- MUST USE the class suffix and namespace mapping in the domain reference. Capability modules in `app/{Domain}/Capabilities/{Capability}` allow flat placement and descriptive technical type names. Named Actions, Queries, Services, and Factories keep their role and injection rules. Entity classes MUST end in `Entity`; Concrete Model classes MUST NOT end in `Model`. Abstract Model classes may use the `Model` suffix. Scope classes MUST end in `Scope` and live under `Models`. At dependency injection points, name injected application and Larastrict collaborators after their full short type name, without a trailing `Contract` or `Interface`. Prefix generic role names with the preceding namespace segment; preserve descriptive import aliases and required inherited names. Ordinary method inputs, DTOs, and external types keep semantic names.
- MUST USE request `Key*` constants in validation rules and input getters.
- MUST USE `Attribute*` and `Relation*` constants for Model identifiers. Read and write values through ordinary properties. MUST USE snake_case for relationship methods, including trait methods.
- MUST USE snake_case application Resource and Inertia prop keys. Build Inertia props through a JsonResource over prepared Entities; pass the original Entity to Blade when it needs the same data.
- MUST USE the installed Larastrict interfaces and namespaces. Verify package source before using provider, route, view, config, or other package APIs.
- MUST USE `boot()` injection and protected dependency properties in Livewire Components, including Filament Pages and Widgets. Use typed parameters in supported Filament callbacks; pass dependencies to directly called helpers. See the Filament and Livewire skill reference for static Resource boundaries and exact UI facade exceptions.
- MUST USE `LaraStrict\Database\Contracts\RunInTransactionActionContract` for application transactions, and generated expectation helpers for contract expectations.

## Controllers

Controllers may implement a focused, endpoint-specific use case. They may check eligibility, call Queries, select behavior through Factories, and coordinate application collaborators.

Extract an Action when the workflow is reused, needs an independent application boundary, or becomes difficult to understand or test in the controller. Do not require an Action solely because a controller contains business conditions or calls multiple collaborators. Keep persistence implementation in Queries.

## DON'T USE

- DON'T USE `toArray()` as transport between application layers. Prefer an Entity over a raw Model when it gives workflow callers a clearer contract; Query result types remain unrestricted.
- DON'T USE Laravel Collections for ordinary in-memory mapping. Prefer arrays and `foreach`; use a Generator when incremental streaming is useful.
- DON'T USE a Query to orchestrate a business workflow. Calls to collaborators, including Actions, are allowed as part of a focused persistence operation. A Query may perform cohesive persistence work involving several SQL statements.
- DON'T USE direct request getters for more than four input properties. Count input properties, not methods. For larger input sets, create an Entity. Use `Wrkflow\GetValue\Laravel\GetValueFormRequest`.
- DON'T USE `new Carbon`, static date construction, or `now()` in application logic. Inject `Carbon\Factory` as `CarbonFactory` for date construction. Static helper methods are exempt. Static Filament Resource methods are exempt from the date factory check, except in verified injectable callbacks; see the supported injection points reference. DON'T USE container resolution for direct class arguments where the [supported injection points](injection-points.md) provide constructor or method injection. Runtime class variables and binding keys may be resolved through an injected container. Providers may wire the container; constructing new value Entities is allowed. Container resolution is allowed in queued Job and Listener `failed()` hooks and framework hooks without a supported injection lifecycle. FormRequest and JsonResource subclasses may use their own `$this->container->make()` where method injection is unavailable. Registered FormRequest injection methods must use method injection.

Formatting and code style belong to ECS. Do not add formatting rules to agent instructions. Read the focused references for detailed conventions.
