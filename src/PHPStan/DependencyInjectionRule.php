<?php declare(strict_types=1);

namespace LaraStrict\Conventions\PHPStan;

use Filament\Resources\Resource;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Require application code to receive collaborators through dependency injection.
 * Report direct service construction, container lookups, and unsupported date or transaction calls.
 *
 * @implements Rule<Expr>
 */
final readonly class DependencyInjectionRule implements Rule
{
    private InjectionPointRegistry $injectionPointRegistry;
    private DependencyInjectionPolicy $policy;

    /**
     * @param list<string> $applicationNamespaces
     * @param list<string> $dependencyConstructionAllowlist
     */
    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private array $applicationNamespaces = ['App'],
        private array $dependencyConstructionAllowlist = [],
        private bool $enabled = true,
        ?InjectionPointRegistry $injectionPointRegistry = null,
    ) {
        $this->injectionPointRegistry = $injectionPointRegistry ?? new InjectionPointRegistry();
        $this->policy = new DependencyInjectionPolicy($reflectionProvider, $this->injectionPointRegistry);
    }

    public function getNodeType(): string
    {
        return Expr::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->enabled === false) {
            return [];
        }

        if (! $this->inApplicationLogic($scope) || $this->injectionPointRegistry->isStaticHelper($scope)) {
            return [];
        }

        if ($node instanceof New_) {
            $name = $node->class instanceof Name ? $scope->resolveName($node->class) : null;

            if ($name !== null && $this->isCollaborator($name) && ! $this->isAllowedConstruction($name)) {
                $message = $this->injectionPointRegistry->isFrameworkUi($scope->getClassReflection())
                    ? 'Inject application services through Livewire boot() or a supported Filament callback; pass dependencies to directly called helpers.'
                    : 'Do not construct application collaborators directly; inject them through the constructor.';

                return [$this->error($message, 'larastrict.dependencies.directConstruction')];
            }
        }

        if ($node instanceof FuncCall && $node->name instanceof Name) {
            $name = strtolower((string) $this->reflectionProvider->resolveFunctionName($node->name, $scope));

            if (DependencyInjectionPolicy::isContainerResolutionFunction($name) && $this->policy->requiresInjection($node, $scope)) {
                return [$this->containerResolutionError($scope)];
            }

            if (DependencyInjectionPolicy::isDateFunction($name) && $this->requiresDateFactory($scope)) {
                return [$this->dateFactoryError($scope)];
            }
        }

        if ($node instanceof StaticCall && $node->class instanceof Name && $node->name instanceof Identifier) {
            $class = $scope->resolveName($node->class);
            $method = strtolower($node->name->toString());

            if ($this->requiresDateFactory($scope) && DependencyInjectionPolicy::isDateClass($class) && DependencyInjectionPolicy::isDateStaticMethod($method)) {
                return [$this->dateFactoryError($scope)];
            }

            if (strcasecmp($class, DB::class) === 0 && $method === 'transaction') {
                return [$this->error('Use RunInTransactionActionContract for transactions.', 'larastrict.dependencies.transaction')];
            }

            if ($this->isFacade($class) && $this->injectionPointRegistry->allowsFrameworkFacade($class, $scope) === false) {
                $message = $this->injectionPointRegistry->isFrameworkUi($scope->getClassReflection())
                    ? 'Inject application services through Livewire boot() or a supported Filament callback; pass dependencies to directly called helpers.'
                    : 'Do not resolve services through facades in application logic; inject them through the constructor.';

                return [$this->error($message, 'larastrict.dependencies.facadeResolution')];
            }
        }

        if ($node instanceof MethodCall && $node->name instanceof Identifier) {
            $method = strtolower($node->name->toString());
            $receiverType = $scope->getType($node->var);

            if (($method === 'make' && $this->isContainerType($receiverType)) ||
                  ($method === 'make' && $node->var instanceof StaticCall && $node->var->class instanceof Name && $node->var->name instanceof Identifier && strcasecmp($scope->resolveName($node->var->class), \Illuminate\Container\Container::class) === 0 && strtolower($node->var->name->toString()) === 'getinstance')) {
                if ($this->policy->requiresInjection($node, $scope) === false) {
                    return [];
                }

                return [$this->containerResolutionError($scope)];
            }

            if ($method === 'transaction' && $this->isConnectionType($receiverType)) {
                return [$this->error('Use RunInTransactionActionContract for transactions.', 'larastrict.dependencies.transaction')];
            }
        }

        if ($node instanceof New_ && $node->class instanceof Name) {
            $class = $scope->resolveName($node->class);

            if (DependencyInjectionPolicy::isDateClass($class) && $this->requiresDateFactory($scope)) {
                return [$this->dateFactoryError($scope)];
            }
        }

        return [];
    }

    private function containerResolutionError(Scope $scope): IdentifierRuleError
    {
        $message = match (true) {
            $this->policy->isInjectableRequestMethod($scope) && in_array(strtolower((string) $scope->getFunction()?->getName()), ['rules', 'authorize'], true) => 'Do not resolve services from the container in FormRequest rules() or authorize(); inject them through the method parameters.',
            $this->injectionPointRegistry->resolutionInjection($scope) === 'method' => 'Do not resolve services from the container here; inject them through the method parameters.',
            $this->injectionPointRegistry->resolutionInjection($scope) === 'function' => 'Do not resolve services from the container here; inject them through the function parameters.',
            $this->injectionPointRegistry->resolutionInjection($scope) === 'boot' => 'Do not resolve services from the container here; inject them through Livewire boot() and keep them in protected properties.',
            $this->injectionPointRegistry->resolutionInjection($scope) === 'callback' => 'Do not resolve services from the container here; inject them through typed Filament callback parameters.',
            default => 'Do not resolve services from the container in application logic; inject them through the constructor.',
        };

        return $this->error($message, 'larastrict.dependencies.containerResolution');
    }

    private function requiresDateFactory(Scope $scope): bool
    {
        $class = $scope->getClassReflection();
        $method = $scope->getFunction();

        return ! ($class instanceof ClassReflection
            && isset($class->getAncestors()[Resource::class])
            && $method instanceof MethodReflection
            && $method->isStatic()
            && $this->injectionPointRegistry->resolutionInjection($scope) !== 'callback');
    }

    private function dateFactoryError(Scope $scope): IdentifierRuleError
    {
        $message = $this->injectionPointRegistry->isFrameworkUi($scope->getClassReflection())
            ? 'Create dates through CarbonFactory. Inject it through Livewire boot() or a supported Filament callback; pass it to directly called helpers.'
            : 'Create dates through the injected CarbonFactory.';

        return $this->error($message, 'larastrict.dependencies.dateFactory');
    }

    private function inApplicationLogic(Scope $scope): bool
    {
        $class = $scope->getClassReflection();

        $namespace = $scope->getNamespace();

        if ($namespace === null) {
            return false;
        }

        $isServiceProvider = $class instanceof ClassReflection && ($class->isSubclassOfClass($this->reflectionProvider->getClass(ServiceProvider::class)) || str_ends_with($class->getName(), 'ServiceProvider'));

        return DependencyInjectionPolicy::isApplicationLogic($namespace, $class?->getName() ?? '', $isServiceProvider, $this->applicationNamespaces);
    }

    private function isCollaborator(string $class): bool
    {
        return DependencyInjectionPolicy::isCollaborator($class, $this->applicationNamespaces) && ! $this->isServiceProvider($class);
    }

    private function isAllowedConstruction(string $class): bool
    {
        foreach ($this->dependencyConstructionAllowlist as $allowed) {
            if ($class === $allowed || str_starts_with($class, rtrim($allowed, '\\') . '\\')) {
                return true;
            }
        }

        return false;
    }

    private function isServiceProvider(string $class): bool
    {
        if (str_ends_with($class, 'ServiceProvider') || str_ends_with($class, 'ServiceProviders')) {
            return true;
        }

        return $this->reflectionProvider->hasClass($class) && $this->reflectionProvider->getClass($class)
            ->isSubclassOfClass($this->reflectionProvider->getClass(ServiceProvider::class));
    }

    private function isFacade(string $class): bool
    {
        $class = ltrim($class, '\\');

        return str_contains(strtolower($class), '\\facades\\');
    }

    private function isContainerType(Type $type): bool
    {
        return (new ObjectType(ContainerContract::class))->isSuperTypeOf($type)->yes() ||
            (new ObjectType(\Illuminate\Container\Container::class))->isSuperTypeOf($type)->yes();
    }

    private function isConnectionType(Type $type): bool
    {
        return (new ObjectType(Connection::class))->isSuperTypeOf($type)->yes() ||
            (new ObjectType(ConnectionInterface::class))->isSuperTypeOf($type)->yes();
    }

    private function error(string $message, string $identifier): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)->identifier($identifier)->build();
    }
}
