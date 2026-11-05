<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan;

use LaraStrict\Conventions\PHPStan\ClassPlacementRule;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Extensions\SdkPlacementExtension;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ClassPlacementRule>
 */
final class ClassPlacementRuleTest extends RuleTestCase
{
    /**
     * @var array<string>
     */
    private array $applicationNamespaces = ['LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming'];

    private bool $enabled = true;
    private bool $sdkExtension = false;

    protected function getRule(): Rule
    {
        return new ClassPlacementRule($this->applicationNamespaces, enabled: $this->enabled, extensions: $this->sdkExtension ? [new SdkPlacementExtension()] : []);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/framework-ui.neon'];
    }

    public function testCapabilityAllowanceRequiresACapabilityName(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Core/Capabilities/MisplacedFactory.php'], [[
            'Class MisplacedFactory must be placed in Factories because its name ends with "Factory".',
            5,
        ]]);
    }

    public function testCapabilityTypesMayUseFlatPlacement(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Core/Capabilities/LazyPaginator/LazyPaginator.php',
            __DIR__ . '/Fixtures/Naming/Core/Capabilities/LazyPaginator/PaginationCursor.php',
            __DIR__ . '/Fixtures/Naming/Core/Capabilities/LazyPaginator/LazyPaginatorFactory.php',
            __DIR__ . '/Fixtures/Naming/Core/Capabilities/LazyPaginator/LazyPaginatorServiceProvider.php',
            __DIR__ . '/Fixtures/Naming/Core/Capabilities/LazyPaginator/LoadPageAction.php',
            __DIR__ . '/Fixtures/Naming/Core/Capabilities/LazyPaginator/LoadPageQuery.php',
        ], []);
    }

    public function testFilamentPanelProviderMayLiveBesideItsPanelClasses(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Filament/FilamentPanelServiceProvider.php',
            __DIR__ . '/Fixtures/Naming/Filament/UnrelatedServiceProvider.php',
        ], [[
            'Class UnrelatedServiceProvider must be placed in <application/domain root> because its name ends with "ServiceProvider".',
            5,
        ]]);
    }

    public function testDisabledRuleAllowsViolations(): void
    {
        $this->enabled = false;

        $this->analyse([__DIR__ . '/Fixtures/Naming/Misplaced/SaveAction.php'], []);
    }

    public function testConformingClassPlacementAndFilename(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Actions/SaveAction.php',
            __DIR__ . '/Fixtures/Naming/Services/SaveService.php',
            __DIR__ . '/Fixtures/Naming/Entities/AccountEntity.php',
            __DIR__ . '/Fixtures/Naming/Queries/FindQuery.php',
            __DIR__ . '/Fixtures/Naming/Queries/Builders/AccountBuilder.php',
            __DIR__ . '/Fixtures/Naming/Models/HasObject/JoinObjectScope.php',
            __DIR__ . '/Fixtures/Naming/DomainServiceProvider.php',
            __DIR__ . '/Fixtures/Naming/Models/Account.php',
            __DIR__ . '/Fixtures/Naming/Models/Concerns/HasFlags.php',
        ], []);
    }

    public function testSuffixRequiresMatchingNamespace(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Misplaced/SaveAction.php'], [[
            'Class SaveAction must be placed in Actions or Http\\<area>\\<endpoint> because its name ends with "Action".',
            5,
        ]]);
    }

    public function testEloquentFactoriesMayLiveBesideModelsIncludingInheritedFactories(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Models/Customer.php',
            __DIR__ . '/Fixtures/Naming/Models/CustomerFactory.php',
            __DIR__ . '/Fixtures/Naming/Models/PreferredCustomerFactory.php',
        ], []);
    }

    public function testModelFactoryExceptionRequiresTheFrameworkTypeAndModelsNamespace(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Models/PaymentFactory.php',
            __DIR__ . '/Fixtures/Naming/Misplaced/CustomerFactory.php',
        ], [
            ['Class PaymentFactory must be placed in Factories because its name ends with "Factory".', 5],
            ['Class CustomerFactory must be placed in Factories or Models because its name ends with "Factory".', 5],
        ]);
    }

    public function testTransformersMayUseSourceToTargetNames(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Transformers/RequestToAccountEntity.php',
            __DIR__ . '/Fixtures/Naming/Transformers/AccountEntityToArray.php',
            __DIR__ . '/Fixtures/Naming/Transformers/ArrayToAccount.php',
            __DIR__ . '/Fixtures/Naming/Transformers/AccountTransformer.php',
        ], []);
    }

    public function testTransformerExceptionRequiresConversionNameAndNamespace(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Transformers/AccountEntity.php',
            __DIR__ . '/Fixtures/Naming/Transformers/Convert.php',
            __DIR__ . '/Fixtures/Naming/Misplaced/RequestToAccountEntity.php',
        ], [
            ['Class AccountEntity must be placed in Entities or Http\\<area>\\<endpoint> because its name ends with "Entity".', 5],
            ['Class Convert in namespace segment Transformers must end with "Transformer". Alternatively, use a SourceToTarget conversion name.', 5],
            ['Class RequestToAccountEntity must be placed in Entities or Http\\<area>\\<endpoint> because its name ends with "Entity".', 5],
        ]);
    }

    public function testConversionNamesStillRequireMatchingNamespacePaths(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Misplaced/ArrayToAccountEntity.php'], [[
            'The file directory must match the declared namespace LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Transformers.',
            5,
        ]]);
    }

    public function testEntitiesRequireTheirSuffixAndModelsMustNotUseIt(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Entities/Account.php',
            __DIR__ . '/Fixtures/Naming/Models/AccountModel.php',
        ], [
            ['Class Account in namespace segment Entities must end with "Entity".', 5],
            ['Eloquent Model class names must not end with "Model"; use the Models namespace.', 7],
        ]);
    }

    public function testHttpTypesMayUseConventionalRoleFolders(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Http/Controllers/LoginController.php',
            __DIR__ . '/Fixtures/Naming/Http/Requests/LoginRequest.php',
            __DIR__ . '/Fixtures/Naming/Http/Resources/LoginResource.php',
        ], []);
    }

    public function testHttpEndpointsMayGroupControllersRequestsResourcesAndEntities(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Http/TenantApi/Login/LoginController.php',
            __DIR__ . '/Fixtures/Naming/Http/TenantApi/Login/LoginRequest.php',
            __DIR__ . '/Fixtures/Naming/Http/TenantApi/Login/LoginResource.php',
            __DIR__ . '/Fixtures/Naming/Http/TenantApi/Login/LoginEntity.php',
        ], []);
    }

    public function testSdkEndpointEntitiesAreAllowed(): void
    {
        $this->sdkExtension = true;
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Confirme/Sdk/Endpoints/Verifiers/DeviceEntity.php',
            __DIR__ . '/Fixtures/Naming/Confirme/Sdk/Endpoints/Verifiers/Nested/DeviceEntity.php',
        ], []);
    }

    public function testSdkEntitiesRequireAnEndpointNamespace(): void
    {
        $this->sdkExtension = true;
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Confirme/Sdk/DeviceEntity.php',
            __DIR__ . '/Fixtures/Naming/Confirme/Sdk/Endpoints/DeviceEntity.php',
            __DIR__ . '/Fixtures/Naming/Confirme/Endpoints/Verifiers/DeviceEntity.php',
        ], [
            ['Class DeviceEntity must be placed in Entities or Sdk\\Endpoints\\* or Http\\<area>\\<endpoint> because its name ends with "Entity".', 5],
            ['Class DeviceEntity must be placed in Entities or Sdk\\Endpoints\\* or Http\\<area>\\<endpoint> because its name ends with "Entity".', 5],
            ['Class DeviceEntity must be placed in Entities or Sdk\\Endpoints\\* or Http\\<area>\\<endpoint> because its name ends with "Entity".', 5],
        ]);
    }

    public function testStandaloneControllersMayLiveDirectlyInAnHttpArea(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Http/TenantWeb/VerifyController.php'], []);
    }

    public function testHttpResourcePlacementDoesNotApplyToOtherResourceKinds(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Misplaced/PanelResource.php',
            __DIR__ . '/Fixtures/Naming/Misplaced/AccountPanelResource.php',
            __DIR__ . '/Fixtures/Naming/Misplaced/AccountResource.php',
        ], [
            ['Class AccountResource must be placed in Http\\Resources or Http\\<area>\\<endpoint> because its name ends with "Resource".', 5],
        ]);
    }

    public function testEndpointActionsAndQueriesAreAllowedButServicesKeepTheirFolder(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Http/TenantApi/Login/LoginAction.php',
            __DIR__ . '/Fixtures/Naming/Http/TenantApi/Login/LoginQuery.php',
            __DIR__ . '/Fixtures/Naming/Http/TenantApi/Login/LoginService.php',
        ], [
            ['Class LoginService must be placed in Services because its name ends with "Service".', 5],
        ]);
    }

    public function testHttpActionsAndQueriesRequireAnEndpointFolder(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Http/TenantApi/LoginAction.php',
            __DIR__ . '/Fixtures/Naming/Http/TenantApi/LoginQuery.php',
        ], [
            ['Class LoginAction must be placed in Actions or Http\\<area>\\<endpoint> because its name ends with "Action".', 5],
            ['Class LoginQuery must be placed in Queries or Http\\<area>\\<endpoint> because its name ends with "Query".', 5],
        ]);
    }

    public function testHttpRoleFoldersAndEndpointDepthRemainEnforced(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Http/Controllers/Login.php',
            __DIR__ . '/Fixtures/Naming/Http/Controllers/LoginEntity.php',
            __DIR__ . '/Fixtures/Naming/Http/TenantApi/LoginEntity.php',
            __DIR__ . '/Fixtures/Naming/Misplaced/LoginController.php',
        ], [
            ['Class Login in namespace segment Http\\Controllers must end with "Controller".', 5],
            ['Class LoginEntity must be placed in Entities or Http\\<area>\\<endpoint> because its name ends with "Entity".', 5],
            ['Class LoginEntity must be placed in Entities or Http\\<area>\\<endpoint> because its name ends with "Entity".', 5],
            ['Class LoginController must be placed in Http\\Controllers or Http\\<area>\\<endpoint> or Http\\<area> because its name ends with "Controller".', 5],
        ]);
    }

    public function testAbstractModelsMayUseModelSuffixWhileEntitiesStillRequireTheirSuffix(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Entities/AbstractAccount.php',
            __DIR__ . '/Fixtures/Naming/Models/AbstractAccountModel.php',
            __DIR__ . '/Fixtures/Naming/Models/TenantAwareModel.php',
        ], [
            ['Class AbstractAccount in namespace segment Entities must end with "Entity".', 5],
        ]);
    }

    public function testConcreteModelsCannotInheritTheModelSuffixException(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Models/TenantAccountModel.php'], [[
            'Eloquent Model class names must not end with "Model"; use the Models namespace.',
            5,
        ]]);
    }

    public function testNamespaceRequiresMatchingSuffix(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Actions/Save.php'], [[
            'Class Save in namespace segment Actions must end with "Action".',
            5,
        ]]);
    }

    public function testModelScopeCapabilityAndNamespaceAreAccepted(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Models/Scopes/ActiveScope.php',
            __DIR__ . '/Fixtures/Naming/Models/HasObject/JoinObjectScope.php',
        ], []);
    }

    public function testBuilderAndProviderNamespaceDepthsAreEnforced(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Queries/Builders/BuildProjection.php',
            __DIR__ . '/Fixtures/Naming/Providers/DomainServiceProvider.php',
        ], [
            [
                'Class BuildProjection in namespace segment Queries\\Builders must end with "Builder".',
                5,
            ],
            [
                'Class DomainServiceProvider must be placed in <application/domain root> because its name ends with "ServiceProvider".',
                5,
            ],
        ]);
    }

    public function testScopesRequireTheirSuffixAndBelongUnderModels(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Models/AccountScope.php',
            __DIR__ . '/Fixtures/Naming/Models/WhereActiveScope.php',
            __DIR__ . '/Fixtures/Naming/Models/Scopes/WhereStateInScope.php',
            __DIR__ . '/Fixtures/Naming/Models/HasObject/WhereObjectIdScope.php',
            __DIR__ . '/Fixtures/Naming/Models/Concerns/AbstractFilterScope.php',
        ], []);
    }

    public function testUnsuffixedEloquentScopesAreReportedIncludingInheritedAndAbstractTypes(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Models/WhereActive.php',
            __DIR__ . '/Fixtures/Naming/Models/Scopes/WhereStateIn.php',
            __DIR__ . '/Fixtures/Naming/Models/HasObject/WhereObjectId.php',
            __DIR__ . '/Fixtures/Naming/Models/Concerns/AbstractFilter.php',
        ], [
            ['Eloquent scope AbstractFilter must end with "Scope".', 7],
            ['Eloquent scope WhereObjectId must end with "Scope".', 7],
            ['Eloquent scope WhereStateIn must end with "Scope".', 7],
            ['Eloquent scope WhereActive must end with "Scope".', 9],
        ]);
    }

    public function testDomainLevelScopesAreRejectedIncludingInheritedAndAbstractScopeTypes(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Scopes/ActiveScope.php',
            __DIR__ . '/Fixtures/Naming/Scopes/WhereActive.php',
            __DIR__ . '/Fixtures/Naming/Scopes/AbstractFilter.php',
        ], [
            ['Class ActiveScope must be placed in Models because its name ends with "Scope".', 5],
            ['Eloquent scope AbstractFilter must be placed in Models or a Models subnamespace.', 7],
            ['Eloquent scope WhereActive must be placed in Models or a Models subnamespace.', 7],
        ]);
    }

    public function testScopeFoldersRequireTheScopeSuffix(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Models/Scopes/Helper.php'], [[
            'Class Helper in namespace segment Models\\Scopes must end with "Scope".',
            5,
        ]]);
    }

    public function testClassesOutsideApplicationNamespaceAreIgnored(): void
    {
        $this->applicationNamespaces = ['App\\'];
        $this->analyse([__DIR__ . '/Fixtures/Naming/Misplaced/SaveAction.php'], []);
    }

    public function testLaravelGlobalProvidersAndSubdomainProviderNamesAreAllowed(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Providers/AppServiceProvider.php',
            __DIR__ . '/Fixtures/Naming/Methods/Certificate/CertificateMethodServiceProvider.php',
            __DIR__ . '/Fixtures/Naming/Methods/Certificate/CertificateServiceProvider.php',
            __DIR__ . '/Fixtures/Naming/Payments/Invoices/InvoicePaymentServiceProvider.php',
        ], []);
    }

    public function testProviderAllowancesDoNotPermitNestedProvidersRoleFoldersOrReversedNames(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Domain/Providers/NestedServiceProvider.php',
            __DIR__ . '/Fixtures/Naming/Methods/Certificate/MethodCertificateServiceProvider.php',
            __DIR__ . '/Fixtures/Naming/Methods/Services/ServiceMethodServiceProvider.php',
        ], [
            ['Class NestedServiceProvider must be placed in <application/domain root> because its name ends with "ServiceProvider".', 5],
            ['Class MethodCertificateServiceProvider must be placed in <application/domain root> because its name ends with "ServiceProvider".', 5],
            ['Class ServiceMethodServiceProvider must be placed in <application/domain root> because its name ends with "ServiceProvider".', 5],
        ]);
    }

    public function testProviderMayLiveAtItsDomainRoot(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Domain/DomainServiceProvider.php'], []);
    }

    public function testFileDirectoryMustMatchDeclaredNamespace(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Naming/Misplaced/NamespaceMismatchService.php'], [[
            'The file directory must match the declared namespace LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Services.',
            5,
        ]]);
    }
}
