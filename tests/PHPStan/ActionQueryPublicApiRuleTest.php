<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan;

use LaraStrict\Conventions\PHPStan\ActionQueryPublicApiRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ActionQueryPublicApiRule>
 */
final class ActionQueryPublicApiRuleTest extends RuleTestCase
{
    private const string APPLICATION_NAMESPACE = 'LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Architecture';

    /**
     * @var array<string>
     */
    private array $applicationNamespaces = [self::APPLICATION_NAMESPACE];

    private bool $enabled = true;

    protected function getRule(): Rule
    {
        return new ActionQueryPublicApiRule(self::createReflectionProvider(), $this->applicationNamespaces, enabled: $this->enabled);
    }

    public function testFlatCapabilityOperationsKeepTheirPublicApiRules(): void
    {
        $this->applicationNamespaces = ['LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming'];
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Core/Capabilities/LazyPaginator/LoadPageAction.php',
            __DIR__ . '/Fixtures/Naming/Core/Capabilities/LazyPaginator/LoadPageQuery.php',
        ], [
            ['Concrete Action and Query classes must define a public execute() method (LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Core\\Capabilities\\LazyPaginator\\LoadPageAction).', 5],
            ['Action and Query classes may expose only execute() publicly; helper() is also public.', 5],
        ]);
    }

    public function testDisabledRuleAllowsViolations(): void
    {
        $this->enabled = false;

        $this->analyse([__DIR__ . '/Fixtures/Architecture/Actions/BadPublicApiAction.php'], []);
    }

    public function testExecuteAndConstructorAreAllowed(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Architecture/Actions/GoodAction.php'], []);
    }

    public function testPublicMethodRequiredByExternalInterfaceIsAllowed(): void
    {
        $this->analyse([
            __DIR__ . '/../External/Contracts/NamedRouteActionContract.php',
            __DIR__ . '/Fixtures/Architecture/Actions/NamedRouteAction.php',
        ], []);
    }

    public function testExternalInterfaceDoesNotAllowUnrelatedPublicMethods(): void
    {
        $this->analyse([
            __DIR__ . '/../External/Contracts/NamedRouteActionContract.php',
            __DIR__ . '/Fixtures/Architecture/Actions/NamedRouteActionWithHelper.php',
        ], [[
            'Action and Query classes may expose only execute() publicly; helper() is also public.',
            7,
        ]]);
    }

    public function testApplicationInterfaceDoesNotAllowAdditionalPublicApi(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Architecture/Contracts/LocalSuffixContract.php',
            __DIR__ . '/Fixtures/Architecture/Actions/LocalContractAction.php',
        ], [[
            'Action and Query classes may expose only execute() publicly; getFileSuffix() is also public.',
            7,
        ]]);
    }

    public function testAdditionalPublicMethodIsReported(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Architecture/Actions/BadPublicApiAction.php'], [[
            'Action and Query classes may expose only execute() publicly; helper() is also public.',
            5,
        ]]);
    }

    public function testConcreteClassMustDefinePublicExecute(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Architecture/Actions/NoExecuteAction.php'], [[
            'Concrete Action and Query classes must define a public execute() method (LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Architecture\\Actions\\NoExecuteAction).',
            5,
        ]]);
    }

    public function testInheritedApplicationPublicMethodIsReported(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Architecture/Support/ActionBase.php',
            __DIR__ . '/Fixtures/Architecture/Actions/InheritedPublicApiAction.php',
        ], [[
            'Action and Query classes may expose only execute() publicly; inherited helper() is also public.',
            7,
        ]]);
    }

    public function testInheritedPublicExecuteSatisfiesConcreteClassContract(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Architecture/Support/ExecuteBase.php',
            __DIR__ . '/Fixtures/Architecture/Actions/InheritedExecuteAction.php',
        ], []);
    }

    public function testAbstractActionMayOmitExecuteButNotExposeHelpers(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Architecture/Actions/AbstractActionBase.php'], [[
            'Action and Query classes may expose only execute() publicly; helper() is also public.',
            5,
        ]]);
    }

    public function testEndpointActionsAndQueriesKeepTheirPublicApiRules(): void
    {
        $this->applicationNamespaces = ['LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming'];
        $this->analyse([
            __DIR__ . '/Fixtures/Naming/Http/TenantApi/Login/LoginAction.php',
            __DIR__ . '/Fixtures/Naming/Http/TenantApi/Login/LoginQuery.php',
        ], [
            ['Concrete Action and Query classes must define a public execute() method (LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Naming\\Http\\TenantApi\\Login\\LoginAction).', 5],
            ['Action and Query classes may expose only execute() publicly; helper() is also public.', 7],
        ]);
    }
}
