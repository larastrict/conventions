# Resources and Inertia

## Public data contracts

Use `snake_case` for application JSON keys and Inertia prop keys, including nested records. Dictionary keys that are identifiers retain their original values. Map camelCase DTO properties explicitly in a Resource. Keep framework-owned Inertia protocol fields unchanged. Apply the output names to the consuming frontend types and accesses too.

Use a `JsonResource` to define the public response. Prefer the installed `LaraStrict\Http\Resources\JsonResource` for its typed Entity support. For an Inertia page, retrieve typed Entities from Queries, prepare the page Entity in the Action or controller, and map that Entity through a Resource into Inertia props. Other Query return types remain unrestricted.

Keep a Resource in `Http/Resources` or beside its Controller and Entity in an [HTTP endpoint folder](http-and-views.md#http-layouts).

Resources serialize prepared data. Keep persistence and relation loading in Queries, and access decisions in Actions or Services. Expose intended fields explicitly. Use child Entities for structured records and child Resources for their serialization. Lists may remain arrays. Resource serialization is the final delivery boundary; do not pass its arrays back into application workflows.

## Share an Entity with Blade

Prepare the Entity once. Use its Resource for the browser and the original Entity for the server-rendered root view. For example, in the controller or a response Factory shared by several controllers:

```php
$orderPage = $getOrderPageQuery->execute($orderId);

return $inertiaResponseFactory
    ->render('Order/Show', (new OrderPageResource($orderPage))->resolve($request))
    ->withViewData(['data' => $orderPage]);
```

The root Blade view documents `$data` as `OrderPageViewEntity` and passes typed children to components through `:data="$data->header"`. Prepare that header Entity with the page data. If the shell needs a different contract, build a `PageShellViewEntity` from the same prepared data and pass it as view `data`. Avoid rebuilding Entities from serialized `$page['props']`.

`withViewData()` supplies the initial HTML view. It does not send the Entity in Inertia JSON responses or rerender persistent Blade regions on client navigation. Keep navigation-dependent UI in the Inertia component, or use the project's existing client update mechanism.

Apply this design through the consumer's existing page response Factory, page Resource, typed header/footer Factories, and root view. Reuse the existing Inertia payload script and mount boundary. The root can read its title and component data from the Entity while the framework `$page` value supplies the Inertia payload.

## Read the implementation

Read [Larastrict JsonResource](../../../../vendor/wrkflow/larastrict/src/Http/Resources/JsonResource.php) and [Laravel JsonResource](../../../../vendor/laravel/framework/src/Illuminate/Http/Resources/Json/JsonResource.php) for typed mapping and `resolve($request)`. When Inertia is installed, read [ResponseFactory](../../../../vendor/inertiajs/inertia-laravel/src/ResponseFactory.php) for `render()` and [Response](../../../../vendor/inertiajs/inertia-laravel/src/Response.php) for `withViewData()` and the initial HTML versus JSON response paths. Links target the installed application dependencies.
