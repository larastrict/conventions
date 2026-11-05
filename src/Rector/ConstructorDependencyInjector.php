<?php declare(strict_types=1);

namespace LaraStrict\Conventions\Rector;

use PhpParser\Modifiers;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Property;
use PHPStan\Reflection\ClassReflection;
use Rector\NodeNameResolver\NodeNameResolver;
use ReflectionNamedType;

/**
 * Add a dependency only when the constructor or Livewire lifecycle can preserve its contract.
 */
final readonly class ConstructorDependencyInjector
{
    public function __construct(
        private NodeNameResolver $nodeNameResolver,
    ) {}

    private function findMethod(Class_ $class, string $name): ?ClassMethod
    {
        foreach ($class->getMethods() as $method) {
            if (strtolower($method->name->toString()) === $name) {
                return $method;
            }
        }

        return null;
    }

    private function assignsProperty(?ClassMethod $method, string $property): bool
    {
        foreach ($method->stmts ?? [] as $statement) {
            if ($statement instanceof Expression && $statement->expr instanceof Assign) {
                $target = $statement->expr->var;

                if ($target instanceof PropertyFetch && $target->var instanceof Variable && $target->var->name === 'this'
                    && $target->name instanceof Identifier && $target->name->toString() === $property) {
                    return true;
                }
            }
        }

        return false;
    }

    public function inheritedDependency(Class_ $class, ClassReflection $reflection, string $type): ?PropertyFetch
    {
        // A promoted property is initialized by the inherited constructor. An
        // overriding constructor may omit parent::__construct(), so skip it.
        if ($this->findMethod($class, '__construct') instanceof ClassMethod) {
            return null;
        }

        $native = $reflection->getNativeReflection();
        $constructor = $native->getConstructor();

        if ($constructor === null) {
            return null;
        }

        foreach ($native->getProperties() as $property) {
            $propertyType = $property->getType();

            if ($property->isStatic() || $property->isPrivate() || ! $property->isPromoted()
                || $property->getDeclaringClass()
                    ->getName() !== $constructor->getDeclaringClass()
                    ->getName()
                || ! $propertyType instanceof ReflectionNamedType || $propertyType->allowsNull()
                || $propertyType->getName() !== $type) {
                continue;
            }

            return new PropertyFetch(new Variable('this'), $property->getName());
        }

        return null;
    }

    public function inject(Class_ $class, ClassReflection $reflection, string $type, string $name, bool $livewire): ?PropertyFetch
    {
        $inherited = $livewire ? null : $this->inheritedDependency($class, $reflection, $type);

        if ($inherited instanceof PropertyFetch) {
            return $inherited;
        }

        $constructor = $this->findMethod($class, '__construct');

        foreach ($livewire ? [] : ($constructor->params ?? []) as $parameter) {
            if ($parameter->var instanceof Variable && $parameter->flags !== 0 && $parameter->type instanceof Name && $this->nodeNameResolver->isName($parameter->type, $type) && is_string($parameter->var->name)) {
                return new PropertyFetch(new Variable('this'), $parameter->var->name);
            }
        }

        foreach ($class->getProperties() as $property) {
            if ($property->isStatic() || ! $property->type instanceof Name || ! $this->nodeNameResolver->isName($property->type, $type) || ($livewire && ! $property->isProtected())) {
                continue;
            }

            $initialization = $livewire ? $this->findMethod($class, 'boot') : $constructor;

            foreach ($property->props as $item) {
                if ($this->assignsProperty($initialization, $item->name->toString())) {
                    return new PropertyFetch(new Variable('this'), $item->name);
                }
            }
        }

        if (($reflection->hasInstanceProperty($name) || $reflection->hasStaticProperty($name)) || $class->getProperty($name) instanceof Property) {
            return null;
        }

        $methodName = $livewire ? 'boot' : '__construct';
        $method = $this->findMethod($class, $methodName);

        // Do not override inherited lifecycle or construction contracts.
        if (! $method instanceof ClassMethod && ($livewire ? $reflection->hasMethod('boot') : $reflection->hasConstructor())) {
            return null;
        }

        if ($method instanceof ClassMethod && (! $method->isPublic() || $method->isStatic() || $method->stmts === null)) {
            return null;
        }

        if ($method instanceof ClassMethod) {
            foreach ($method->params ?? [] as $parameter) {
                if (! $parameter->type instanceof Name || ! $this->nodeNameResolver->isName($parameter->type, $type)) {
                    continue;
                }

                if (! $livewire || ! $parameter->var instanceof Variable || ! is_string($parameter->var->name)) {
                    return null;
                }

                $existingName = $parameter->var->name;

                if ($parameter->default !== null || $parameter->variadic || $reflection->hasInstanceProperty($existingName) || $reflection->hasStaticProperty($existingName) || $class->getProperty($existingName) instanceof Property) {
                    return null;
                }

                $class->stmts[] = new Property(Modifiers::PROTECTED, [new PropertyItem($existingName)], type: new FullyQualified($type));
                $method->stmts = [new Expression(new Assign(new PropertyFetch(new Variable('this'), $existingName), new Variable($existingName))), ...($method->stmts ?? [])];

                return new PropertyFetch(new Variable('this'), $existingName);
            }
        }

        foreach ($method->params ?? [] as $parameter) {
            if ($parameter->default !== null || $parameter->variadic || ($parameter->var instanceof Variable && $parameter->var->name === $name)) {
                return null;
            }
        }

        $method ??= new ClassMethod($methodName, [
            'flags' => Modifiers::PUBLIC,
            'stmts' => [],
        ]);
        $method->params[] = new Param(new Variable($name), type: new FullyQualified($type), flags: $livewire ? 0 : Modifiers::PRIVATE);

        if ($livewire) {
            $class->stmts[] = new Property(Modifiers::PROTECTED, [new PropertyItem($name)], type: new FullyQualified($type));
            $method->stmts = [new Expression(new Assign(new PropertyFetch(new Variable('this'), $name), new Variable($name))), ...($method->stmts ?? [])];
            $method->returnType ??= new Identifier('void');
        }

        if (! $this->findMethod($class, $methodName) instanceof ClassMethod) {
            $class->stmts[] = $method;
        }

        return new PropertyFetch(new Variable('this'), $name);
    }
}
