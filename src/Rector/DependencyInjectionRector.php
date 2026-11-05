<?php declare(strict_types=1);

namespace LaraStrict\Conventions\Rector;

use Carbon\CarbonImmutable;
use Carbon\Factory as CarbonFactory;
use Carbon\FactoryImmutable;
use Filament\Facades\Filament;
use Illuminate\Contracts\Cookie\QueueingFactory;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\ServiceProvider;
use LaraStrict\Conventions\PHPStan\DependencyInjectionPolicy;
use LaraStrict\Conventions\PHPStan\InjectionPointRegistry;
use LaraStrict\Conventions\PHPStan\ParameterNamePolicy;
use Livewire\Component;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeVisitor;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Convert supported instance dependencies; leave static Resource call chains for explicit refactoring.
 */
final class DependencyInjectionRector extends AbstractRector
{
    private readonly InjectionPointRegistry $injectionPointRegistry;
    private readonly DependencyInjectionPolicy $policy;
    private readonly ParameterNamePolicy $parameterNamePolicy;

    public function __construct(
        private readonly ConstructorDependencyInjector $dependencyInjector,
        private readonly ReflectionProvider $reflectionProvider,
    )
    {
        $this->injectionPointRegistry = new InjectionPointRegistry();
        $this->policy = new DependencyInjectionPolicy($reflectionProvider, $this->injectionPointRegistry);
        $this->parameterNamePolicy = new ParameterNamePolicy($reflectionProvider);
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Inject collaborators through constructors or Livewire boot().', [new CodeSample(
            'final class LoadQuery { public function execute() { return now(); } }',
            'final class LoadQuery { public function __construct(private \\Carbon\\Factory $carbonFactory) {} public function execute() { return $this->carbonFactory->now(); } }',
        )]);
    }

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $scope = ScopeFetcher::fetch($node);
        $class = $scope->getClassReflection();

        if (! $class instanceof ClassReflection || $node->isAbstract() || $class->isAnonymous()
            || ! DependencyInjectionPolicy::isApplicationLogic(
                $scope->getNamespace() ?? '',
                $class->getName(),
                $class->isSubclassOfClass($this->reflectionProvider->getClass(ServiceProvider::class)),
                ['App'],
            )) {
            return null;
        }

        $livewire = isset($class->getAncestors()[Component::class]);

        if (! $livewire && ! $this->injectionPointRegistry->supportsConstructor($class)) {
            return null;
        }

        $changed = false;

        foreach ($node->getMethods() as $method) {
            if ($method->isStatic() || $this->isName($method, '__construct')) {
                continue;
            }

            $this->traverseNodesWithCallable($method->stmts ?? [], function (Node $expression) use ($node, $class, $livewire, &$changed): Node|int|null {
                if ($expression instanceof ClassLike || ($expression instanceof Closure && $expression->static) || ($expression instanceof ArrowFunction && $expression->static)) {
                    return NodeVisitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
                }

                if (! $expression instanceof Expr || $expression instanceof ClassConstFetch) {
                    return null;
                }

                $replacement = $this->replacement($expression);

                if ($replacement === null) {
                    return null;
                }

                [$type, $propertyName, $methodName, $arguments, $nativeConversion] = $replacement;
                $property = $livewire ? null : $this->dependencyInjector->inheritedDependency($node, $class, $type);

                if (! $property instanceof PropertyFetch && ! $livewire && $type === CarbonFactory::class && $nativeConversion === null) {
                    $property = $this->dependencyInjector->inheritedDependency($node, $class, FactoryImmutable::class);

                    if ($property instanceof PropertyFetch) {
                        // Keep the mutable result of now()/Carbon calls while
                        // sharing the inherited clock and its settings.
                        $nativeConversion = 'toMutable';
                    }
                }

                $property ??= $this->dependencyInjector->inject($node, $class, $type, $propertyName, $livewire);

                if (! $property instanceof PropertyFetch) {
                    return null;
                }

                $changed = true;

                if ($methodName === null) {
                    return $property;
                }

                $call = new MethodCall($property, $methodName, $arguments);

                return $nativeConversion === null ? $call : new MethodCall($call, $nativeConversion);
            });
        }

        return $changed ? $node : null;
    }

    /**
     * @return array{string, string, ?string, array<Node\Arg>, ?string}|null
     */
    private function replacement(Expr $expression): ?array
    {
        if ($expression instanceof FuncCall && $expression->name instanceof Name && ! $expression->isFirstClassCallable()) {
            $scope = ScopeFetcher::fetch($expression);
            $name = $this->reflectionProvider->resolveFunctionName($expression->name, $scope);

            if ($name !== null && DependencyInjectionPolicy::isDateFunction($name) && in_array(strtolower($name), ['now', 'today'], true)) {
                return [CarbonFactory::class, 'carbonFactory', $name, $expression->getArgs(), null];
            }

            if ($name !== null && DependencyInjectionPolicy::isContainerResolutionFunction($name) && $this->policy->requiresInjection($expression, $scope)) {
                $argument = $expression->getArgs()[0] ?? null;

                if (count($expression->getArgs()) === 1 && $argument !== null && $argument->name === null && $argument->value instanceof ClassConstFetch && $argument->value->class instanceof Name && $this->isName($argument->value->name, 'class')) {
                    $type = $this->getName($argument->value->class);

                    if (DependencyInjectionPolicy::isApplicationClass($type, ['App']) && $this->reflectionProvider->hasClass($type) && $this->injectionPointRegistry->supportsConstructor($this->reflectionProvider->getClass($type))) {
                        $parameter = new Param(new Variable('dependency'), type: new FullyQualified($type));
                        $name = $this->parameterNamePolicy->expectedParameterName($parameter, $type);

                        return [$type, $name, null, [], null];
                    }
                }
            }
        }

        if ($expression instanceof StaticCall && $expression->class instanceof Name && ! $expression->isFirstClassCallable()) {
            $className = $this->getName($expression->class);
            $methodName = $this->getName($expression->name);

            if ($methodName === null) {
                return null;
            }

            if (DependencyInjectionPolicy::isDateClass($className)
                && in_array(strtolower(ltrim($className, '\\')), ['carbon\\carbon', 'carbon\\carbonimmutable'], true)
                && in_array(strtolower($methodName), ['now', 'today', 'parse', 'instance'], true)) {
                $immutable = $className === CarbonImmutable::class;

                return [$immutable ? FactoryImmutable::class : CarbonFactory::class, $immutable ? 'carbonFactoryImmutable' : 'carbonFactory', $methodName, $expression->getArgs(), null];
            }

            if ($className === Filament::class && ! $this->injectionPointRegistry->allowsFrameworkFacade($className, ScopeFetcher::fetch($expression))) {
                return ['Filament\\FilamentManager', 'filamentManager', $methodName, $expression->getArgs(), null];
            }

            if ($className === Cookie::class && $methodName === 'queue') {
                return [QueueingFactory::class, 'cookieFactory', $methodName, $expression->getArgs(), null];
            }
        }

        if ($expression instanceof New_ && $expression->class instanceof Name && $expression->getArgs() === []) {
            $className = $this->getName($expression->class);

            if (in_array($className, ['DateTime', 'DateTimeImmutable'], true)) {
                $immutable = $className === 'DateTimeImmutable';

                return [$immutable ? FactoryImmutable::class : CarbonFactory::class, $immutable ? 'carbonFactoryImmutable' : 'carbonFactory', 'now', [], $immutable ? 'toDateTimeImmutable' : 'toDateTime'];
            }
        }

        return null;
    }
}
