# Larastrict Rector rules

`extension-rector.php` registers Larastrict rules with the existing StrictPHP set.
Run a dry run before applying changes. Use repeated `--only` options when you want
to limit a run to specific rules.

```bash
vendor/bin/rector process app --dry-run \
  --only='LaraStrict\Conventions\Rector\DependencyInjectionRector'
```

## Class names and placement

`ClassNamingRector` updates class declarations and PHP references. It does not move
files. Run it from the consumer root, review the diff, then move renamed files to
match their PSR-4 paths before running PHPStan.

```bash
vendor/bin/rector process app --dry-run
vendor/bin/rector process app
```

This changes `WhereStateIn` to `WhereStateInScope` and updates PHP references and
exact fully qualified class strings. It also handles missing role suffixes,
concrete Model suffix removal, namespace/file mismatches, and placement with one
clear destination. Existing provider, capability, HTTP-slice, Transformer
conversion-name, and ModelFactory allowances remain in the policy.

`ClassPlacementPolicy` supplies both the PHPStan diagnostic and the correction.
Configure Rector with the same policy options as PHPStan. `ClassNamingRector`
accepts `policy: new ClassPlacementPolicy(...)` or an `onlyClasses` list to limit
the class declarations it changes. PHP references are still processed.
The rule skips ambiguous placement
alternatives and unsafe class declarations. Review external consumers and
non-PHP references. Run ECS after a rename.

PHPStan package tags do not configure Rector. Add each package placement
extension to the Rector policy too. For `wrkflow/php-api-sdk-builder`, use:

```php
->withConfiguredRule(\LaraStrict\Conventions\Rector\ClassNamingRector::class, [
    'policy' => new \LaraStrict\Conventions\PHPStan\ClassPlacementPolicy(
        extensions: [new \WrkFlow\PHPstan\ApiSdkBuilder\SdkClassPlacementExtension()],
    ),
])
```

This keeps SDK Entities beside their endpoints. Do not add an `Entities` folder
for these package-owned data objects.

Rector changes the PHP code only. Move each renamed file after applying the
changes. Then run PHPStan to verify class and file placement.

## Shared policy

The rules share decisions with PHPStan:

| Policy | Consumers |
| --- | --- |
| `ClassPlacementPolicy` | Class placement diagnostics, rename planning |
| `ParameterNamePolicy` and `InjectionPointRegistry` | Parameter diagnostics and renames; injection contexts |
| `DependencyInjectionPolicy` | Dependency diagnostics and supported automatic replacements |
| `Architecture\IdentifierPolicy` | Model/relationship/request diagnostics and all three identifier fixes |

PHPStan reports violations. Rector adds separate checks that decide whether a
local rewrite is safe. These checks may skip a valid diagnostic. Supported fix
subsets, constructor edits, scope preservation, collisions, and dynamic code
checks stay in Rector.

## Identifier constants

- `Identifier\ModelIdentifierConstantsRector` reuses exact `Attribute*` and
  `Relation*` values in supported Eloquent calls. It adds missing Attribute
  declarations only for Model definition arrays such as `hidden` and `casts`.
  It does not invent attributes from unknown query columns or SQL aliases.
- `Identifier\RequestKeyConstantsRector` adds or reuses `Key*` constants for
  supported FormRequest rules and input/header calls. A header such as
  `X-Api-Key` becomes `KeyXApiKey`. Unsupported expressions are left unchanged.
- `Identifier\RelationIdentifierConstantsRector` adds missing `Relation*`
  declarations for verified Eloquent relationship methods. Shared capability
  declarations belong on the Interface; consuming Models do not receive copies.

The three class names above are relative to `LaraStrict\Conventions\Rector`.
Existing matching constant values are reused. Conflicting names are skipped.
Run the shared capability Interfaces with their Models when creating constants.

## Calculated SQL aliases

`Identifier\QueryAliasConstantsRector` handles direct
`selectRaw(...)->value('alias')` chains on a known database Builder. It extracts
simple trailing SQL aliases into private `Alias*` constants on the owning class
and uses the constant in both the SQL and the result lookup. Literal and
interpolated SQL keep their bindings. Existing conflicts are skipped.

The shared identifier policy recognizes the matching projection as a result key.
It does not recommend or create a Model `Attribute*` constant for that alias.
More complex projection chains, named arguments, and unsupported SQL stay unchanged.

## Injectable parameter names

`InjectableParameterNameRector` uses the same `ParameterNamePolicy` and default
injection registry as PHPStan. It renames supported injection parameters and
private promoted properties, including private readonly promotions. It updates
local references, closure captures, property accesses, and parameter PHPDoc.

The rule skips relevant named-argument uses and contextual binding strings found
in the consumer project. For constructor parameters, named arguments on known
unrelated constructor calls do not block the rename. Calls to the same class or
its descendants remain guarded. Named arguments on a different explicitly named
method have their own signature and do not block the rename. For method injection,
constructor arguments are also independent. Calls to the same method name,
dynamic calls, and reflection or invocation forwarding remain guarded. It skips
required inherited parameters, public/protected promoted
properties, variable or property-name collisions, traits, dynamic property or
variable access, and name-dependent helpers such as `compact()`. These cases
need explicit migration rather than a local rename. Source outside the consumer
project is not covered by the scan; review signatures used by external callers.

## Dependency injection

`DependencyInjectionRector` preserves the exact UI facade exceptions from
`InjectionPointRegistry`. For example, it keeps `Filament::getPanel()` in a
Livewire Page or Widget. The installed facade and manager may declare different
return types, so a permitted facade call must not be replaced automatically.
Application services and middleware still use injected collaborators.

`DependencyInjectionRector` handles supported instance contexts in `App`:

- Replace `now()`, `today()`, and supported Carbon `now`, `today`, `parse`, and
  `instance` calls with injected factories. Keep explicit arguments. Use
  `FactoryImmutable` for CarbonImmutable calls. Zero-argument native DateTime
  construction uses a factory and converts back to the native date type.
- Replace direct `app(Type::class)` and `resolve(Type::class)` collaborator
  lookups where the default injection registry supports the owner and target.
  Dynamic keys, construction arguments, and unsupported entry points are skipped.
- Replace Filament facade calls with `FilamentManager`, and `Cookie::queue()`
  with `QueueingFactory`, in supported instance contexts.
- Constructor-inject ordinary components. Livewire Components receive protected
  dependency properties assigned by `boot()` on each request. Reuse an existing
  promoted dependency or an initialized, typed property where possible. An
  inherited public/protected, non-nullable constructor-promoted dependency can
  be reused when the child inherits that constructor. An inherited immutable
  Carbon factory can supply mutable dates through `toMutable()`; this keeps the
  inherited clock settings. Private, nullable, uninitialized, and overridden
  constructor cases are skipped.

The rule skips static methods, static closures, Resource helper chains, inherited
construction or boot contracts that need an override, optional/variadic lifecycle
parameters, and property-name collisions. It does not assume arbitrary framework
callbacks support injection. It uses default registry definitions;
project PHPStan service overrides do not automatically configure Rector.

Review constructor call sites after adding required dependencies. Container
resolution supplies them, but explicit `new` calls and tests may need arguments.
Review date factory bindings, timezone settings, configured date classes, and
clock controls. The rule does not copy Laravel Date facade configuration into a
new Carbon factory. Illuminate Support Carbon calls are left unchanged.

## Validation

Tests compare before/after fixtures and cover already-correct code, skipped
contexts, existing dependencies, and merging `boot()`. Runtime checks verify
protected dependency replacement on repeated boots and native immutable dates
with independent injected clocks. A successful dry run does not replace
application tests or final repository QA.
