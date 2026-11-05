# Model conventions

Use `Order` in `Models`; concrete models must not use `OrderModel`. Abstract models may use the `Model` suffix, such as `TenantAwareModel`. Data objects use the `Entity` suffix in `Entities`, such as `OrderEntity`.

Use model-specific folder placement where it fits the model's capability. For models that use the Larastrict `HasObject` pattern, keep these types under `Models/HasObject`: the `HasObject` trait, `HasObjectInterface`, and `JoinObjectScope`. Put model helper traits in `Models/Concerns`.

Keep all application Eloquent scope classes under `Models` and require the `Scope` suffix, including abstract base scopes. A scope may live beside its Model, in `Models/Scopes`, or in a capability folder such as `Models/HasObject`. This applies when the class implements `Illuminate\Database\Eloquent\Scope` directly or through a base such as Larastrict `AbstractScope` or `AbstractInScope`. Use `Models/WhereStateInScope.php` or `Models/Scopes/WhereStateInScope.php`, with a matching filename and namespace. Ordinary Model classes do not need a `Scope` suffix.

Keep a model's Eloquent Factory beside the Model: `Models/VerifierDevice.php` and `Models/VerifierDeviceFactory.php`. The Factory extends `Illuminate\Database\Eloquent\Factories\Factory`, directly or through a shared base, and keeps the `Factory` suffix. Use its `@extends Factory<VerifierDevice>` PHPDoc for the model type. Ordinary application Factories still belong in `Factories`.

The interface defines `RelationObject` and `AttributeObjectId`. The trait declares `@phpstan-require-implements` for that interface and provides the `initializeHasObject` hook. Do not add a `Trait` suffix solely to the `HasObject` trait name. A model interface does not need a container binding.

Declare a model constant for each database column name using the `Attribute` prefix, for example `AttributeEmail`. Use it when naming the column in queries and relations. Use the model property or PHPDoc to describe the column value. Use Eloquent's `CREATED_AT`, `UPDATED_AT`, and `DELETED_AT` constants for timestamp identifiers. Use attribute constants as cast-map keys. Read and write values through ordinary properties. Define `Relation*` constants for relationship names, including those supplied by model-capability interfaces. Share generic constants when the same external key is used in multiple places; do not impose a blanket ban on strings.

Relationship method names MUST use `snake_case`, for example `order_items()`. Match the `Relation*` constant value to the method name: `RelationOrderItems = 'order_items'`. This applies to relation methods supplied by capability traits too.

Document attributes with `@property` and computed values or relations with `@property-read`. Describe relation return types with the installed Eloquent generic types. Use casts for enums, dates, and scalar values so PHPDoc matches runtime values. Load relations explicitly in Queries rather than triggering queries during output conversion.

A shared base Model should own real shared behavior, such as UUIDs or tenant scoping. Use capability traits and interfaces for optional behavior. A model capability interface may declare `@phpstan-require-extends` for Eloquent Model; the implementing trait declares `@phpstan-require-implements`.

Select the columns needed by the result Entity. Keep consumers independent of partially loaded Models. To filter by related data, prefer key filters, SQL subqueries, or `whereHas()` over loading related Models. Keep public UUIDs separate from internal foreign-key IDs.

## Read the implementation

When changing a model Factory, read Laravel's installed [Factory](../../../../vendor/laravel/framework/src/Illuminate/Database/Eloquent/Factories/Factory.php) for model mapping and the `definition()` contract.

When changing relation definitions, read the relevant methods in Laravel's [HasRelationships](../../../../vendor/laravel/framework/src/Illuminate/Database/Eloquent/Concerns/HasRelationships.php). When extending scopes, read [AbstractScope](../../../../vendor/wrkflow/larastrict/src/Database/Scopes/AbstractScope.php). For an identifier diagnostic, inspect its branch in [ModelIdentifierRule](../../../../vendor/larastrict/conventions/src/PHPStan/ModelIdentifierRule.php).
