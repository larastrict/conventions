# Generate contract expectations

1. Check the application's tests and [Larastrict's Laravel test helpers](../../../../vendor/wrkflow/larastrict/src/Testing/Laravel/) for an existing generated Assert. Many Laravel interfaces already have helpers under `LaraStrict\Testing\Laravel`, such as `LaraStrict\Testing\Laravel\Contracts\View\FactoryAssert` for `Illuminate\Contracts\View\Factory`. Inspect its methods and Expectation classes against the installed interface before reuse. For application behavior that needs a test boundary, define its Contract in `Contracts`, bind it in the domain provider, and inject it into the consumer. Keep existing Laravel interfaces as the test boundary.
2. When no compatible Assert is available, generate it with the installed command. From the application root, generate helpers for the Contract or Laravel interface. Use the repository's PHP container when required. The command accepts a PSR-4 file path or a fully qualified class name:

   ```sh
   php artisan make:expectation app/Order/Contracts/SendOrderActionContract.php
   # Equivalent input:
   php artisan make:expectation 'App\Order\Contracts\SendOrderActionContract'
   # A Laravel interface, only when a compatible helper is unavailable:
   php artisan make:expectation 'Illuminate\Contracts\View\Factory'
   ```

3. Select the test namespace offered by the command. Review the generated `*Assert` and `*Expectation` files. Output names and constructor arguments depend on the public methods and installed generator version. Inspect the generated API before using it.
4. Regenerate after changing a tested Contract. Check parameter types, return types, and every generated method against that Contract. Do not hand-write, copy, or edit generated helpers. If generation fails, report the failure and fix the generator environment. If it reports a missing `nette/php-generator` dependency, use the project's development dependency workflow.
5. Configure expected inputs, return values, and calls in the test, then inject the generated Assert as the dependency. Use `LaraStrict\Testing\Assert\Traits\AssertExpectationManagerTrait` on the PHPUnit test or its base class so unused expectations fail. Assert the observable result as well as collaborator calls. Prefer generated helpers over Mockery.

Keep the transaction attempt count in expectations for `RunInTransactionActionContract`. Use the repository's existing data providers and assertion helpers. When using `AssertContents`, run the test to create the expected output file; do not write that file by hand.

## Read the implementation

Read [MakeExpectationCommand](../../../../vendor/wrkflow/larastrict/src/Testing/Commands/MakeExpectationCommand.php) for generation, [FactoryAssert](../../../../vendor/wrkflow/larastrict/src/Testing/Laravel/Contracts/View/FactoryAssert.php) as a Laravel-interface example, and [AssertExpectationManagerTrait](../../../../vendor/wrkflow/larastrict/src/Testing/Assert/Traits/AssertExpectationManagerTrait.php) for call verification. These links resolve in the installed application.
