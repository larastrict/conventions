# HTTP and views

Requests validate input. Controllers shape the HTTP response. Controllers may implement a focused, endpoint-specific use case. They may check eligibility, call Queries, select behavior through Factories, and coordinate application collaborators.

Extract an Action when the workflow is reused, needs an independent application boundary, or becomes difficult to understand or test in the controller. Do not require an Action solely because a controller contains business conditions or calls multiple collaborators. Keep persistence implementation in Queries.

Use `Wrkflow\GetValue\Laravel\GetValueFormRequest` for typed Laravel request values. The package namespace spelling is `Wrkflow`. For up to four input properties, expose direct typed `get*` methods or return an Entity. Count input properties, not getter methods. For more properties, build a typed Entity from validated input and pass it onward.

Use typed Entities for larger request input sets and view data; prefer them for structured Action results. Query return types remain unrestricted. Do not pass `toArray()` output as a DTO. Keep an Entity's fields focused on the data the consumer needs. For lists, scalar options, and actual list values, arrays and indexing are appropriate; use Entities for nested records when they clarify the contract.

## Request keys and validation

Define request field names as `Key*` constants on the Request. Reuse them in validation rules, typed getters, nested paths, and callers that build the payload. This applies even when the key is used once. Validation rule names such as `required` and `string` are not field keys.

```php
public const string KeyName = 'name';

public function rules(): array
{
    return [self::KeyName => ['required', 'string']];
}

public function getName(): string
{
    return $this->data->getRequiredString(self::KeyName);
}
```

Compose nested keys from constants, for example `self::KeyItems . '.*.' . self::KeyName`. Read from the same input source that validation checks, including query filters and pagination. Validate route parameters and restrict accepted model types. Inject Transformers for complex payload conversion.

## HTTP layouts

An **endpoint slice** is Larastrict's HTTP form of a vertical slice: code owned by one HTTP use case stays together inside its domain. Use `Http/{area}/{endpoint}`. The area identifies the HTTP audience or interface, such as `TenantApi` or `TenantWeb`; the endpoint names the use case, such as `Login` or `RejectVerificationUiState`.

Prefer an endpoint slice when its Request, Resource, Action, Query, or Entity serves that endpoint alone. Scope these classes to the slice's namespace. Add only the files the endpoint needs. The layouts below remain valid within the same domain.

| Layout | Placement |
| --- | --- |
| By type | Controllers in `Http/Controllers`, Requests in `Http/Requests`, and Resources in `Http/Resources`; DTOs in the domain's `Entities` |
| Endpoint slice | Controller, Request, Resource, Entity, Action, and Query together in `Http/{area}/{endpoint}` as needed |
| Standalone Controller | Controller directly in `Http/{area}`, such as `Http/TenantWeb/ShowOrderController.php`; no paired Request or Resource required |

For example:

```text
UserLogin/Http/TenantApi/Login/
    LoginUserController.php
    LoginUserRequest.php
    LoginUserResource.php
    LoginUserEntity.php
    LoginUserAction.php
    GetLoginUserQuery.php
    LoginUser.http
```

Each class uses the matching suffix, filename, and namespace, such as `App\UserLogin\Http\TenantApi\Login\LoginUserEntity`. A slice needs at least an area and endpoint after `Http`; version segments may add depth. It does not need separate type folders.

Keep slice classes focused on their endpoint. When behavior serves multiple endpoints, move it to the domain's `Actions`, `Queries`, or `Services` and inject it into each slice. Move shared DTOs to `Entities`; shared Requests and Resources can use `Http/Requests` and `Http/Resources`. Reuse domain behavior instead of coupling one slice to another slice's implementation. Actions and Queries retain constructor injection and public `execute()`; a Controller may own a focused endpoint workflow.

JetBrains HTTP Client `.http` request files may live beside their Controller, in an endpoint slice or directly in `Http/{area}` for a standalone Controller. Use them for runnable request examples. They are optional and do not require a paired Request or Resource class.

These Resource paths apply to HTTP serializers such as JsonResource. Other framework resources, such as Filament panel resources, keep their framework layout.

A controller coordinates its endpoint workflow or calls an Action, then creates its response. Extract a response Factory only when response construction is shared by multiple controllers.

## Blade data

A view receives a named outer data key:

```php
return view('Order::ShowOrderPage', ['data' => $orderPage]);
```

Views receive a typed `*Entity` class from the domain's `Entities` folder or the endpoint's HTTP folder. In a normal Blade view, document the variable with PHPDoc and access properties, such as `$data->title`.

In a Blade component, declare props before PHPDoc:

```blade
@props(['data'])
@php /** @var \App\Order\Entities\OrderSummaryViewEntity $data */ @endphp
<h1>{{ $data->title }}</h1>
```

Use typed child Entities for structured links, groups, and menus. Keep simple component options as scalar props. Prepare URLs and access decisions before rendering. Do not treat the Entity as an associative array. Read [providers](providers.md) when adding or registering views and components. Verify installed interfaces and consumer namespace conventions before adding framework integration.

Read [Resources and Inertia](resources-and-inertia.md) for response keys, JsonResource mapping, and sharing prepared Entities with Blade. Translate internal failures to public errors at the HTTP boundary; do not expose internal exception details.

Before changing request conversion, read the installed [GetValueFormRequest](../../../../vendor/wrkflow/php-get-typed-value/src/Laravel/GetValueFormRequest.php) and the [DTO reference](dto.md).
