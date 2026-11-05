# Providers and registration

Check the installed Larastrict source before using provider capabilities. The application provider is named `{Domain}ServiceProvider` in the domain root, for example `App\Order\OrderServiceProvider`. A module provider may live at `app/{Domain}/Capabilities/{Capability}/{Capability}ServiceProvider.php`; register it through the application's existing provider setup. Implement only capability interfaces the provider needs. Capability names and namespaces below describe the validated package source and can change.

Application-wide Laravel ServiceProvider subclasses may live directly in `App\Providers`, such as `AppServiceProvider`, `RouteServiceProvider`, and `TelescopeServiceProvider`. Keep domain bindings in the domain provider; nested domain `Providers` folders remain disallowed.

At a subdomain root, also allow `{SingularSubdomain}{SingularParentDomain}ServiceProvider`. For `App\Methods\Certificate`, both `CertificateServiceProvider` and `CertificateMethodServiceProvider` are valid. For `App\Payments\Invoices`, `InvoicePaymentServiceProvider` is valid. Use the immediate parent domain, in that order; role folders such as `Services` and `Models` are not subdomains. Existing `{Domain}ServiceProvider` names remain valid. Capability providers keep `{Capability}ServiceProvider` at the capability root.

`AbstractServiceProvider` and `AbstractBaseServiceProvider` are under `LaraStrict\Providers`. `AbstractServiceProvider` runs the package registration and boot pipes. The base class exposes `laraLoadViewsFrom()`, `laraLoadTranslationsFrom()`, and `laraLoadProviderConfigFrom()`.

- `LaraStrict\Contracts\HasRoutes` discovers files in `Http/routes` using the service file name: `{name}_web.php`, `{name}_api.php`, and, with `HasVersionedApiRoutes`, `{name}_api_v{version}.php`. For `OrderServiceProvider`, the files are `Http/routes/order_web.php`, `Http/routes/order_api.php`, and `Http/routes/order_api_v1.php`. The version capability provides `apiVersions()` and adds an `api/v{version}/...` prefix.
- `HasCustomPrefixRoutes` provides `getRoutePrefix()`. `HasCustomRoutes` provides `getCustomRoutes()` for additional route files, registration Actions, or closures. `HasCustomServiceFileName` customizes the service file name.
- `HasViews` loads `{serviceRootDir}/Views` under the service name. A file `Views/ShowOrderPage.blade.php` is then addressed as `Order::ShowOrderPage`.
- `HasViewComponents` registers the provider's `{Domain}\\Components` namespace. Anonymous components are discovered in lowercase `Views/components`, for example `Views/components/order-summary.blade.php`.
- `HasTranslations` loads `{serviceRootDir}/Translations` under the service name. `LaraStrict\Providers\Contracts\HasConfig` merges `{serviceRootDir}/Config/{serviceFileName}.php` under the service file name; for `OrderServiceProvider`, the conventional file is `Config/order.php`.
- `HasViewComposers` calls `bootViewComposers($serviceName, $viewFactory)` during boot.
- `LaraStrict\Console\Contracts\HasSchedule` enables the provider's `schedule()` method in console. `HasScheduleOnEnvironments` limits it through `scheduleEnvironments()`; `HasScheduleOnProduction` limits it to production.

Use the installed capability names, including `HasRoutes`, `HasVersionedApiRoutes`, `HasCustomPrefixRoutes`, `HasCustomRoutes`, `HasCustomServiceFileName`, `HasViews`, `HasViewComponents`, `HasTranslations`, `HasConfig`, `HasViewComposers`, and `HasSchedule` where applicable. Do not copy names, route methods, or registration patterns from old examples without checking source.

Put domain contract bindings in the domain provider. Use contextual bindings or tags when selection depends on a consumer or runtime choice. Keep route declarations in discovered route files. Use provider-resolved config and view namespaces in application code.

## Typed config

Expose domain config through a class extending `LaraStrict\Config\AbstractProviderConfig`. Its protected `getServiceProvider(): string` returns the domain provider class. This links typed getters to the provider's config file. Share key constants between that file and the typed class. Normalize values in typed getters. Keep deliberate runtime mutation behind typed setters; do not scatter `config()` calls through application logic.

## Strategy registration

Keep container lifetime and selection in the provider. Use a scoped binding for request state; do not retain it in a singleton. Preserve `parent::register()` when overriding package registration.

For factory-selected strategies, tag implementations by their shared Interface and supply the tagged list to the Factory. In the provider's `register()` method:

```php
$this->app->tag([CardPaymentService::class], PaymentServiceInterface::class);
$this->app
    ->when(PaymentServiceFactory::class)
    ->needs('$services')
    ->give($this->giveTaggedImplementation(PaymentServiceInterface::class));
```

The Factory accepts a typed list of implementations in `$services` and maps them by the runtime selector. Another provider can extend the same tag without changing the Factory. Use contextual bindings when only one consumer needs a different implementation.

## Read the implementation

Read [AbstractServiceProvider](../../../../vendor/wrkflow/larastrict/src/Providers/AbstractServiceProvider.php) for registration and tagged implementations, [CreateAppServiceProviderAction](../../../../vendor/wrkflow/larastrict/src/Providers/Actions/CreateAppServiceProviderAction.php) for discovery names, and [AbstractProviderConfig](../../../../vendor/wrkflow/larastrict/src/Config/AbstractProviderConfig.php) for typed config ownership.
