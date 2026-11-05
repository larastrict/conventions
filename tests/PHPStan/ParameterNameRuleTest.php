<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan;

use LaraStrict\Conventions\PHPStan\InjectionPointRegistry;
use LaraStrict\Conventions\PHPStan\ParameterNameRule;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Contracts\CreateOrderQueryContract;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Framework\InjectionHandler;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ParameterNameRule>
 */
final class ParameterNameRuleTest extends RuleTestCase
{
    /**
     * @var array<string>
     */
    private array $applicationNamespaces = ['LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming'];

    private bool $enabled = true;
    private ?InjectionPointRegistry $injectionPointRegistry = null;

    /**
     * @var array<class-string>
     */
    private array $excludedTypes = [];

    /**
     * @var array<string>
     */
    private array $componentNamespaces = ['LaraStrict\\'];

    /**
     * @var array<string>
     */
    private array $additionalComponentNamespaces = [];

    protected function getRule(): Rule
    {
        return new ParameterNameRule(
            self::createReflectionProvider(),
            $this->applicationNamespaces,
            enabled: $this->enabled,
            componentNamespaces: $this->componentNamespaces,
            excludedTypes: $this->excludedTypes,
            injectionPointRegistry: $this->injectionPointRegistry,
            additionalComponentNamespaces: $this->additionalComponentNamespaces,
        );
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/framework-ui.neon'];
    }

    public function testFlatCapabilityCollaboratorsAreNamedButValuesKeepSemanticNames(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Core/Capabilities/LazyPaginator/LazyPaginatorFactory.php'], [[
            'Parameter $query should be named $loadPageQuery for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Core\\Capabilities\\LazyPaginator\\LoadPageQuery.',
            7,
        ]]);
    }

    public function testFlatCapabilityConstructorsKeepInjectionNaming(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Core/Capabilities/LazyPaginator/LoadPageQuery.php'], [
            ['Parameter $clock should be named $carbonFactory for Carbon\\Factory.', 7],
        ]);
    }

    public function testLivewireBootIsInjectableButItsConstructorIsNot(): void
    {
        $this->applicationNamespaces = ['App'];
        $this->analyse([__DIR__ . '/Fixtures/FrameworkUi/ScreenService.php'], [[
            'Parameter $clock should be named $carbonFactory for Carbon\\Factory.',
            14,
        ]]);
    }

    public function testDisabledRuleAllowsViolations(): void
    {
        $this->enabled = false;

        $this->analyse([__DIR__ . '/Fixtures/Naming/Services/BadParameterService.php'], []);
    }

    public function testSingleObjectParametersUseTypeNamesAndAllowRolePrefixes(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Services/GoodInjectionService.php'], []);
    }

    public function testIncorrectObjectParameterNameIsReported(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Services/BadParameterService.php'], [[
            'Parameter $request should be named $createOrderQuery for LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Contracts\CreateOrderQueryContract.',
            9,
        ]]);
    }

    public function testDataObjectParameterNamesAreNotEnforced(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/ParameterExamples/BadEntityParameter.php'], []);
    }

    public function testComponentRolesStripContractAndInterfaceButKeepConverterTargetNames(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Services/GoodInjectionService.php'], []);
    }

    public function testExternalInterfacesAndOtherValueTypesKeepSemanticNames(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Services/ExternalInputService.php'], []);
    }

    public function testInputValuesAreNotNamedAsInjectedServices(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/ParameterExamples/GetValueParameters.php',
            __DIR__ . '/Fixtures/Naming/Services/RuntimeInputService.php',
        ], []);
    }

    public function testNonInjectableDeclaringClassesAreIgnored(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/ParameterExamples/BadParameters.php',
            __DIR__ . '/Fixtures/Naming/ParameterExamples/BadRoleParameters.php',
        ], []);
    }

    public function testTypesCanBeExcluded(): void
    {
        $this->excludedTypes = [CreateOrderQueryContract::class];
        $this->analyse([__DIR__ . '/Fixtures/Naming/Services/BadParameterService.php'], []);
    }

    public function testExternalComponentNamespacesCanBeAdded(): void
    {
        $this->componentNamespaces[] = 'Illuminate';
        $this->analyse([__DIR__ . '/Fixtures/Naming/Services/ExternalDependencyService.php'], [[
            'Parameter $value should be named $repository for Illuminate\\Contracts\\Config\\Repository.',
            9,
        ]]);
    }

    public function testPackageCollaboratorNamespacesPreserveTheDefaultNamespaces(): void
    {
        $this->additionalComponentNamespaces = ['Illuminate'];
        $this->analyse([__DIR__ . '/Fixtures/Naming/Services/ExternalDependencyService.php'], [[
            'Parameter $value should be named $repository for Illuminate\\Contracts\\Config\\Repository.',
            9,
        ]]);
        $this->analyse([__DIR__ . '/Fixtures/Naming/Services/BadParameterService.php'], [[
            'Parameter $request should be named $createOrderQuery for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Contracts\\CreateOrderQueryContract.',
            9,
        ]]);
    }

    public function testComponentRolesRejectUnrelatedParameterNames(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Services/BadInjectionService.php'], [
            ['Parameter $strategy should be named $paymentService for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Interfaces\\PaymentServiceInterface.', 16],
            ['Parameter $service should be named $saveService for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Services\\SaveService.', 17],
            ['Parameter $action should be named $saveAction for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Actions\\SaveAction.', 18],
            ['Parameter $converter should be named $requestToAccountEntity for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Transformers\\RequestToAccountEntity.', 19],
            ['Parameter $builder should be named $accountBuilder for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Queries\\Builders\\AccountBuilder.', 20],
            ['Parameter $query should be named $findQuery for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Queries\\FindQuery.', 21],
            ['Parameter $factory should be named $paymentFactory for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Factories\\PaymentFactory.', 22],
            ['Parameter $manager should be named $paymentManager for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Managers\\PaymentManager.', 23],
        ]);
    }

    public function testFrameworkMethodNamesAndAmbiguousTypesAreIgnored(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/ParameterExamples/QueryRunner.php',
            __DIR__ . '/Fixtures/Naming/ParameterExamples/CustomQueryRunner.php',
            __DIR__ . '/Fixtures/Naming/ParameterExamples/BaseRunner.php',
            __DIR__ . '/Fixtures/Naming/ParameterExamples/ExtendedRunner.php',
        ], []);
    }

    public function testClassesOutsideConfiguredApplicationNamespaceAreIgnored(): void
    {
        $this->applicationNamespaces = ['App\\'];
        $this->analyse([__DIR__ . '/Fixtures/Naming/Services/BadParameterService.php'], []);
    }

    public function testCarbonFactoryIsAnInjectedCollaborator(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Services/ClockService.php'], [[
            'Parameter $clock should be named $carbonFactory for Carbon\\Factory.',
            9,
        ]]);
    }

    public function testGenericRoleNamesUseTheirNamespaceAndPreserveDescriptiveAliases(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Services/GenericNamesService.php'], [
            ['Parameter $clock should be named $carbonFactory for Carbon\\Factory.', 18],
            ['Parameter $service should be named $paymentService for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Services\\Payment\\ServiceInterface.', 25],
            ['Parameter $builder should be named $reportBuilder for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Builders\\Report\\BuilderContract.', 26],
            ['Parameter $action should be named $workflowAction for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Actions\\Workflow\\Action.', 27],
            ['Parameter $factory should be named $carbonFactory for Carbon\\Factory.', 30],
            ['Parameter $query should be named $catalogQuery for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Queries\\Catalog\\Query.', 31],
            ['Parameter $transformer should be named $outputTransformer for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Transformers\\Output\\Transformer.', 32],
        ]);
    }

    public function testGroupedControllerActionDependenciesAreChecked(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Http/TenantApi/Store/StoreController.php'], [[
            'Parameter $action should be named $saveAction for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Actions\\SaveAction.',
            10,
        ]]);
    }

    public function testStandaloneControllerInjectionPointsAreChecked(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Http/TenantWeb/VerifyController.php'], [
            ['Parameter $service should be named $saveService for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Services\\SaveService.', 10],
            ['Parameter $action should be named $saveAction for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Actions\\SaveAction.', 12],
        ]);
    }

    public function testEndpointOperationsUseInjectionNamingOnlyForConstructors(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Http/TenantApi/Login/LoginQuery.php'], [
            ['Parameter $action should be named $loginAction for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Http\\TenantApi\\Login\\LoginAction.', 9],
            ['Parameter $service should be named $saveService for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Services\\SaveService.', 9],
        ]);
    }

    public function testRequestInjectionMethodsAreCheckedButDataHelpersAreNot(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Http/TenantApi/Store/StoreRequest.php'], [
            ['Parameter $service should be named $saveService for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Services\\SaveService.', 11],
            ['Parameter $query should be named $createOrderQuery for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Contracts\\CreateOrderQueryContract.', 16],
            ['Parameter $service should be named $saveService for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Services\\SaveService.', 31],
            ['Parameter $service should be named $saveService for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Services\\SaveService.', 36],
        ]);
    }

    public function testCustomMethodInjectionIsOptInThroughTheSharedRegistry(): void
    {
        $fixture = __DIR__ . '/Fixtures/Naming/Framework/CustomHandler.php';
        $this->injectionPointRegistry = new InjectionPointRegistry(additionalMethodInjection: [
            InjectionHandler::class => ['process'],
        ]);
        $this->analyse([$fixture], [[
            'Parameter $value should be named $saveService for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Services\\SaveService.',
            9,
        ]]);
    }

    public function testUnregisteredFrameworkMethodsKeepSemanticNames(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Framework/CustomHandler.php'], []);
    }

    public function testJobCommandAndSeederInjectionMethodsAreChecked(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Jobs/SendJob.php',
            __DIR__ . '/Fixtures/Naming/Console/SendCommand.php',
            __DIR__ . '/Fixtures/Naming/Seeders/SendSeeder.php',
        ], [
            ['Parameter $service should be named $saveService for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Services\\SaveService.', 9],
            ['Parameter $service should be named $saveService for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Services\\SaveService.', 9],
            ['Parameter $service should be named $saveService for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Services\\SaveService.', 9],
        ]);
    }

    public function testInheritedNamesArePreservedButNewDependenciesAreChecked(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Services/InheritedConstructorService.php'], [[
            'Parameter $helper should be named $saveService for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Services\\SaveService.',
            10,
        ]]);
    }

    public function testEloquentFactoriesAreNotInjectableDeclaringClasses(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Factories/InputModelFactory.php'], []);
    }

    public function testListenerEventsAndJobConstructorInputsAreNotMethodInjected(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Listeners/QueuedListener.php',
            __DIR__ . '/Fixtures/Naming/Jobs/SyncJob.php',
        ], [
            ['Parameter $query should be named $createOrderQuery for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Contracts\\CreateOrderQueryContract.', 9],
            ['Parameter $service should be named $saveService for LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Services\\SaveService.', 15],
        ]);
    }
}
