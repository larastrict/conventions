<?php declare(strict_types=1);

namespace LaraStrict\Conventions\PHPStan;

use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Shared classification rules for the dependency diagnostic and Rector fixer.
 */
final readonly class DependencyInjectionPolicy
{
    private const array DateFunctions = [
        'now', 'today', 'date_create', 'date_create_immutable',
        'date_create_from_format', 'date_create_immutable_from_format',
    ];
    private const array DateClasses = ['carbon\\carbon', 'carbon\\carbonimmutable', 'datetime', 'datetimeimmutable'];
    private const array DateStaticMethods = [
        'now', 'today', 'yesterday', 'tomorrow', 'parse', 'rawparse', 'instance', 'make',
        'minvalue', 'maxvalue', 'fromserialized',
    ];
    private const array CollaboratorRoles = [
        'Action', 'Query', 'Service', 'Factory', 'Manager', 'Resolver', 'Handler',
        'Repository', 'Transformer', 'Client', 'Gateway',
    ];
    private const array CollaboratorNamespaces = [
        'Actions', 'Queries', 'Services', 'Factories', 'Managers', 'Resolvers',
        'Handlers', 'Repositories', 'Transformers', 'Gateways',
    ];

    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private InjectionPointRegistry $injectionPointRegistry,
    ) {}

    /**
     * @param list<string> $applicationNamespaces
     */
    public static function isApplicationNamespace(string $namespace, array $applicationNamespaces): bool
    {
        foreach ($applicationNamespaces as $root) {
            $root = trim($root, '\\');

            if ($namespace === $root || str_starts_with($namespace, $root . '\\')) {
                return true;
            }
        }

        return false;
    }

    public static function isExcludedApplicationNamespace(string $namespace): bool
    {
        return preg_match('~(?:^|\\\\)(?:ServiceProviders?|Providers|Models)(?:\\\\|$)~i', $namespace) === 1;
    }

    /**
     * @param list<string> $applicationNamespaces
     */
    public static function isApplicationLogic(string $namespace, string $class, bool $isServiceProvider, array $applicationNamespaces): bool
    {
        return ! $isServiceProvider
            && ! str_ends_with($class, 'ServiceProvider')
            && self::isApplicationNamespace($namespace, $applicationNamespaces)
            && ! self::isExcludedApplicationNamespace($namespace);
    }

    public static function isDateFunction(string $name): bool
    {
        return in_array(strtolower(ltrim($name, '\\')), self::DateFunctions, true);
    }

    public static function isDateClass(string $class): bool
    {
        return in_array(strtolower(ltrim($class, '\\')), self::DateClasses, true);
    }

    public static function isDateStaticMethod(string $method): bool
    {
        $method = strtolower($method);

        return in_array($method, self::DateStaticMethods, true) || str_starts_with($method, 'create');
    }

    public static function isContainerResolutionFunction(string $name): bool
    {
        return in_array(strtolower(ltrim($name, '\\')), ['app', 'resolve'], true);
    }

    /**
     * @param list<string> $applicationNamespaces
     */
    public static function isApplicationClass(string $class, array $applicationNamespaces): bool
    {
        $class = ltrim($class, '\\');
        foreach ($applicationNamespaces as $root) {
            $root = trim($root, '\\');

            if (str_starts_with($class, $root . '\\')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $applicationNamespaces
     */
    public static function isCollaborator(string $class, array $applicationNamespaces): bool
    {
        $class = ltrim($class, '\\');

        if (! self::isApplicationClass($class, $applicationNamespaces)) {
            return false;
        }

        $segments = explode('\\', $class);

        if (in_array('Entities', $segments, true) || in_array('Models', $segments, true)
            || preg_match('~(?:DTO|Data|Entity|Exception|Value|ViewEntity)$~i', $class) === 1
            || str_ends_with($class, 'ServiceProvider')) {
            return false;
        }

        $short = substr($class, (int) strrpos('\\' . $class, '\\'));
        foreach (self::CollaboratorRoles as $role) {
            if (str_ends_with($short, $role)) {
                return true;
            }
        }

        foreach (self::CollaboratorNamespaces as $segment) {
            if (in_array($segment, $segments, true)) {
                return true;
            }
        }

        return false;
    }

    private function allowsContainerResolution(Expr $node, Scope $scope): bool
    {
        $class = $scope->getClassReflection();

        if (! $class instanceof ClassReflection || $this->isInjectableRequestMethod($scope)) {
            return false;
        }

        if ($node instanceof MethodCall && ($node->var instanceof PropertyFetch || $node->var instanceof Variable)
            && $this->isContainerType($scope->getType($node->var)) && $this->injectionPointRegistry->allowsContainerComposition($scope)) {
            return true;
        }

        $classType = new ObjectType($class->getName());

        // Laravel invokes failure hooks directly, without method injection.
        if (strtolower((string) $scope->getFunction()?->getName()) === 'failed'
            && (new ObjectType(ShouldQueue::class))->isSuperTypeOf($classType)->yes()) {
            return true;
        }

        if (! $node instanceof MethodCall || ! $node->var instanceof PropertyFetch) {
            return false;
        }

        $property = $node->var;

        if (! $property->var instanceof Variable || $property->var->name !== 'this'
            || ! $property->name instanceof Identifier || $property->name->toString() !== 'container') {
            return false;
        }

        return (new ObjectType(FormRequest::class))->isSuperTypeOf($classType)->yes()
            || (new ObjectType(JsonResource::class))->isSuperTypeOf($classType)->yes();
    }

    public function requiresInjection(Expr $node, Scope $scope): bool
    {
        return ($node instanceof FuncCall || $node instanceof MethodCall)
            && $this->hasClassArgument($node)
            && $this->injectionPointRegistry->resolutionInjection($scope) !== null
            && $this->allowsContainerResolution($node, $scope) === false;
    }

    private function hasClassArgument(FuncCall|MethodCall $node): bool
    {
        if ($node->isFirstClassCallable()) {
            return false;
        }

        foreach ($node->getArgs() as $position => $argument) {
            if ($argument->name !== null && in_array($argument->name->toString(), ['abstract', 'name'], true) === false) {
                continue;
            }

            if ($argument->name === null && $position !== 0) {
                continue;
            }

            $value = $argument->value;

            return ($value instanceof ClassConstFetch && $value->class instanceof Name && $value->name instanceof Identifier && strtolower($value->name->toString()) === 'class')
                || ($value instanceof String_ && $this->reflectionProvider->hasClass($value->value));
        }

        return false;
    }

    public function isInjectableRequestMethod(Scope $scope): bool
    {
        $class = $scope->getClassReflection();
        $method = $scope->getFunction();

        return $class instanceof ClassReflection
            && $method instanceof MethodReflection
            && strtolower($method->getName()) !== '__construct'
            && (new ObjectType(FormRequest::class))->isSuperTypeOf(new ObjectType($class->getName()))->yes()
            && $this->injectionPointRegistry->supportsInjectionMethod($class, $method);
    }

    private function isContainerType(Type $type): bool
    {
        return (new ObjectType(ContainerContract::class))->isSuperTypeOf($type)->yes()
            || (new ObjectType(\Illuminate\Container\Container::class))->isSuperTypeOf($type)->yes();
    }
}
