<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan;

use App\Billing\Actions\ChargeAction;
use App\Billing\Factories\ScopedClientFactory;
use App\Probe\Framework\Handler;
use LaraStrict\Conventions\PHPStan\DependencyInjectionRule;
use LaraStrict\Conventions\PHPStan\InjectionPointRegistry;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<DependencyInjectionRule>
 */
final class DependencyInjectionRuleTest extends RuleTestCase
{
    /**
     * @var list<string>
     */
    private array $dependencyConstructionAllowlist = [];

    private bool $enabled = true;
    private ?InjectionPointRegistry $injectionPointRegistry = null;

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/dependencies.neon', __DIR__ . '/framework-ui.neon'];
    }

    protected function getRule(): Rule
    {
        return new DependencyInjectionRule(self::createReflectionProvider(), ['App'], $this->dependencyConstructionAllowlist, enabled: $this->enabled, injectionPointRegistry: $this->injectionPointRegistry);
    }

    public function testFlatCapabilityActionsKeepConstructorInjectionRules(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Dependencies/Billing/Capabilities/Paging/LoadPageAction.php'], [[
            'Do not resolve services from the container in application logic; inject them through the constructor.',
            9,
        ]]);
    }

    public function testDisabledRuleAllowsViolations(): void
    {
        $this->enabled = false;

        $this->analyse([__DIR__ . '/Fixtures/Dependencies/BadDependencies.php'], []);
    }

    public function testLivewireUsesBootInjectionAndOnlyAllowsFrameworkFacades(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/FrameworkUi/ScreenService.php'], [
            ['Inject application services through Livewire boot() or a supported Filament callback; pass dependencies to directly called helpers.', 19],
            ['Do not resolve services from the container here; inject them through Livewire boot() and keep them in protected properties.', 20],
            ['Create dates through CarbonFactory. Inject it through Livewire boot() or a supported Filament callback; pass it to directly called helpers.', 21],
        ]);
    }

    public function testStaticResourcesAllowDatesButRetainInstanceDateAndApplicationFacadeChecks(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/FrameworkUi/StaticResource.php'], [
            ['Inject application services through Livewire boot() or a supported Filament callback; pass dependencies to directly called helpers.', 16],
            ['Create dates through CarbonFactory. Inject it through Livewire boot() or a supported Filament callback; pass it to directly called helpers.', 39],
        ]);
    }

    public function testFilamentFacadeExceptionDoesNotApplyToApplicationServices(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/FrameworkUi/OrdinaryService.php'], [[
            'Do not resolve services through facades in application logic; inject them through the constructor.',
            11,
        ]]);
    }

    public function testEvaluatedFilamentCallbacksRequireTypedDependencyParameters(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/FrameworkUi/CallbackResource.php'], [
            ['Do not resolve services from the container here; inject them through typed Filament callback parameters.', 15],
            ['Create dates through CarbonFactory. Inject it through Livewire boot() or a supported Filament callback; pass it to directly called helpers.', 16],
            ['Create dates through CarbonFactory. Inject it through Livewire boot() or a supported Filament callback; pass it to directly called helpers.', 17],
            ['Create dates through CarbonFactory. Inject it through Livewire boot() or a supported Filament callback; pass it to directly called helpers.', 18],
        ]);
    }

    public function testStaticHelpersAreExemptButInstanceMethodsKeepDependencyChecks(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Dependencies/StaticHelpers.php'], [
            ['Create dates through the injected CarbonFactory.', 25],
            ['Create dates through the injected CarbonFactory.', 51],
            ['Do not resolve services from the container here; inject them through typed Filament callback parameters.', 61],
        ]);
    }

    public function testReportsLocatorDateFacadeAndTransactionPatterns(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Dependencies/ChargeAction.php', __DIR__ . '/Fixtures/Dependencies/BadDependencies.php'], [
            ['Do not resolve services from the container in application logic; inject them through the constructor.', 14],
            ['Do not resolve services from the container in application logic; inject them through the constructor.', 15],
            ['Do not construct application collaborators directly; inject them through the constructor.', 16],
            ['Create dates through the injected CarbonFactory.', 17],
            ['Create dates through the injected CarbonFactory.', 18],
            ['Create dates through the injected CarbonFactory.', 19],
            ['Do not resolve services through facades in application logic; inject them through the constructor.', 20],
            ['Use RunInTransactionActionContract for transactions.', 21],
        ]);
    }

    public function testAllowsFactoriesEntitiesModelsAndExcludesProviderWiring(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Dependencies/InvoiceEntity.php',
            __DIR__ . '/Fixtures/Dependencies/VendorBuilder.php',
            __DIR__ . '/Fixtures/Dependencies/AllowedDependencies.php',
            __DIR__ . '/Fixtures/Dependencies/RootProvider.php',
            __DIR__ . '/Fixtures/Dependencies/ExcludedWiring.php',
            __DIR__ . '/Fixtures/Dependencies/UnrelatedMethods.php',
        ], []);
    }

    public function testAllowsConfiguredCollaboratorConstruction(): void
    {
        $this->dependencyConstructionAllowlist = [ChargeAction::class];
        $this->analyse([__DIR__ . '/Fixtures/Dependencies/ChargeAction.php', __DIR__ . '/Fixtures/Dependencies/BadDependencies.php'], [
            ['Do not resolve services from the container in application logic; inject them through the constructor.', 14],
            ['Do not resolve services from the container in application logic; inject them through the constructor.', 15],
            ['Create dates through the injected CarbonFactory.', 17],
            ['Create dates through the injected CarbonFactory.', 18],
            ['Create dates through the injected CarbonFactory.', 19],
            ['Do not resolve services through facades in application logic; inject them through the constructor.', 20],
            ['Use RunInTransactionActionContract for transactions.', 21],
        ]);
    }

    public function testOtherDateConstructorsAreRejectedButPureDateChecksRemainAllowed(): void
    {
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/Fixtures/Dependencies/MoreDatesAction.php']);
        self::assertCount(4, $errors);
        foreach ($errors as $error) {
            self::assertSame('larastrict.dependencies.dateFactory', $error->getIdentifier());
        }
    }

    public function testUnsuffixedModelAndEntityValuesDoNotBecomeServiceDependencies(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Dependencies/PaymentGateway.php',
            __DIR__ . '/Fixtures/Dependencies/Gateway.php',
            __DIR__ . '/Fixtures/Dependencies/CreateValuesAction.php',
        ], []);
    }

    public function testAllowsContainerResolutionAtFrameworkLifecycleBoundaries(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Dependencies/AllowedFrameworkDependencies.php'], []);
    }

    public function testFrameworkExceptionsDoNotDisableOtherDependencyChecks(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Dependencies/BadFrameworkDependencies.php'], [
            ['Create dates through the injected CarbonFactory.', 38],
            ['Do not resolve services from the container here; inject them through the method parameters.', 48],
            ['Do not resolve services from the container here; inject them through the method parameters.', 49],
            ['Create dates through the injected CarbonFactory.', 54],
            ['Do not resolve services through facades in application logic; inject them through the constructor.', 55],
            ['Use RunInTransactionActionContract for transactions.', 56],
            ['Do not construct application collaborators directly; inject them through the constructor.', 57],
        ]);
    }

    public function testRequiresMethodInjectionInRequestRulesAndAuthorize(): void
    {
        $message = 'Do not resolve services from the container in FormRequest rules() or authorize(); inject them through the method parameters.';

        $this->analyse([__DIR__ . '/Fixtures/Dependencies/RequestMethodDependencies.php'], [
            [$message, 11],
            [$message, 12],
            [$message, 13],
            [$message, 20],
            [$message, 21],
            [$message, 22],
        ]);
    }

    public function testContainerResolutionRequiresAKnownInjectionPath(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Dependencies/InjectionScopes.php'], [...$this->defaultScopeErrors(), ...$this->frameworkScopeErrors()]);
    }

    public function testRegistryCanAddMethodsNamespacesAndNamedFunctions(): void
    {
        $this->injectionPointRegistry = new InjectionPointRegistry(
            additionalMethodInjection: [
                Handler::class => ['process'],
            ],
            additionalNamespaceMethodInjection: [
                '\\Workflows\\' => ['handle'],
            ],
            functions: ['\\App\\Probe\\runWithDependencies'],
        );

        $this->analyse([__DIR__ . '/Fixtures/Dependencies/InjectionScopes.php'], [
            ...$this->defaultScopeErrors(),
            ['Do not resolve services from the container here; inject them through the method parameters.', 85],
            ['Do not resolve services from the container here; inject them through the method parameters.', 100],
            ['Do not resolve services from the container here; inject them through the function parameters.', 113],
            ...$this->frameworkScopeErrors(),
        ]);
    }

    public function testRegistryCanAddConstructorNamespacesAndBaseClasses(): void
    {
        $this->injectionPointRegistry = new InjectionPointRegistry(
            additionalConstructorNamespaces: ['\\Widgets\\'],
            additionalConstructorBaseClasses: [Handler::class],
        );
        $constructorMessage = 'Do not resolve services from the container in application logic; inject them through the constructor.';

        $this->analyse([__DIR__ . '/Fixtures/Dependencies/InjectionScopes.php'], [
            [$constructorMessage, 12],
            [$constructorMessage, 13],
            [$constructorMessage, 14],
            ...$this->defaultScopeErrors(),
            [$constructorMessage, 85],
            [$constructorMessage, 90],
            ...$this->frameworkScopeErrors(),
        ]);
    }

    public function testScopedFactoryClassDependenciesRequireExplicitCompositionRegistration(): void
    {
        $message = 'Do not resolve services from the container in application logic; inject them through the constructor.';

        $this->analyse([__DIR__ . '/Fixtures/Dependencies/ScopedClientFactory.php'], [
            [$message, 20],
            [$message, 29],
            [$message, 34],
            [$message, 35],
            [$message, 36],
            ['Create dates through the injected CarbonFactory.', 37],
            ['Do not construct application collaborators directly; inject them through the constructor.', 38],
        ]);
    }

    public function testCompositionRegistrationAllowsOnlyContainerCallsInTheRegisteredMethodsAndClosures(): void
    {
        $this->injectionPointRegistry = new InjectionPointRegistry(containerCompositionMethods: [
            ScopedClientFactory::class => ['createScoped', 'createUnsafe'],
        ]);
        $message = 'Do not resolve services from the container in application logic; inject them through the constructor.';

        $this->analyse([__DIR__ . '/Fixtures/Dependencies/ScopedClientFactory.php'], [
            [$message, 29],
            [$message, 34],
            [$message, 35],
            [$message, 36],
            ['Create dates through the injected CarbonFactory.', 37],
            ['Do not construct application collaborators directly; inject them through the constructor.', 38],
        ]);
    }

    public function testDynamicBindingsAreAllowedWhileDirectClassArgumentsRemainChecked(): void
    {
        $message = 'Do not resolve services from the container in application logic; inject them through the constructor.';

        $this->analyse([__DIR__ . '/Fixtures/Dependencies/DynamicLookupFactory.php'], [
            [$message, 31],
            [$message, 32],
            [$message, 33],
            [$message, 34],
            [$message, 35],
            [$message, 36],
            [$message, 37],
            [$message, 38],
        ]);
    }

    /**
     * @return list<array{string, int}>
     */
    private function defaultScopeErrors(): array
    {
        $constructorMessage = 'Do not resolve services from the container in application logic; inject them through the constructor.';
        $methodMessage = 'Do not resolve services from the container here; inject them through the method parameters.';

        return [
            [$methodMessage, 31],
            [$methodMessage, 32],
            [$constructorMessage, 47],
            [$constructorMessage, 48],
            [$constructorMessage, 63],
            [$methodMessage, 71],
        ];
    }

    /**
     * @return list<array{string, int}>
     */
    private function frameworkScopeErrors(): array
    {
        $methodMessage = 'Do not resolve services from the container here; inject them through the method parameters.';

        return [
            [$methodMessage, 132],
            ['Do not resolve services from the container in application logic; inject them through the constructor.', 137],
            [$methodMessage, 145],
            [$methodMessage, 153],
            [$methodMessage, 161],
            [$methodMessage, 168],
        ];
    }
}
