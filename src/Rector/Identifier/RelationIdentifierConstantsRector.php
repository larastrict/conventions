<?php declare(strict_types=1);

namespace LaraStrict\Conventions\Rector\Identifier;

use Illuminate\Database\Eloquent\Model;
use LaraStrict\Conventions\Architecture\IdentifierPolicy;
use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Add Relation* constants for public methods with reflected Eloquent relation return types.
 */
final class RelationIdentifierConstantsRector extends AbstractRector
{
    /**
     * @param list<string> $applicationNamespaces
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly array $applicationNamespaces = ['App'],
    ) {}

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add Relation* constants for declared public Eloquent relationship methods.', [
            new CodeSample(
                <<<'CODE'
public function customer(): BelongsTo
{
    // ...
}
CODE
                ,
                <<<'CODE'
public const RelationCustomer = 'customer';

public function customer(): BelongsTo
{
    // ...
}
CODE
                ,
            ),
        ]);
    }

    public function getNodeTypes(): array
    {
        return [Class_::class, Interface_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (($node instanceof Class_ || $node instanceof Interface_) === false) {
            return null;
        }

        $scope = $node->getAttribute('scope');

        if (! $scope instanceof Scope || ! ($class = $scope->getClassReflection()) instanceof ClassReflection || ! $this->inApplicationNamespace($class) || ! $this->isModelOrModelContract($class, $scope)) {
            return null;
        }

        $constants = $class->getNativeReflection()
            ->getConstants();
        foreach ($class->getInterfaces() as $interface) {
            $constants += $interface->getNativeReflection()
                ->getConstants();
        }

        if ($node instanceof Class_) {
            foreach ($node->implements as $implementedInterface) {
                $interfaceName = $scope->resolveName($implementedInterface);

                if ($this->reflectionProvider->hasClass($interfaceName)) {
                    $constants += $this->reflectionProvider->getClass($interfaceName)
                        ->getNativeReflection()
                        ->getConstants();
                }
            }
        }

        // Another class-level rule can revisit this node before reflection refreshes.
        foreach ($node->stmts as $statement) {
            if (! $statement instanceof ClassConst) {
                continue;
            }

            foreach ($statement->consts as $constant) {
                if ($constant->value instanceof String_) {
                    $constants[$constant->name->toString()] = $constant->value->value;
                }
            }
        }

        $additions = [];
        foreach ($node->getMethods() as $method) {
            if ($method->isPublic() === false || $method->isStatic() || $method->name->toString() === '__construct') {
                continue;
            }

            $name = $method->name->toString();

            if ($class->hasMethod($name) === false) {
                continue;
            }

            $returnType = $class->getMethod($name, $scope)
                ->getVariants()[0]
                ->getReturnType();

            if (IdentifierPolicy::returnsRelation($returnType) === false) {
                continue;
            }

            if ($node instanceof Class_ && $this->interfaceDeclaresRelationMethod($node, $scope, $name)) {
                continue;
            }

            if (IdentifierPolicy::matchingConstantName($constants, $name, 'Relation') !== null) {
                continue;
            }

            $constantName = IdentifierPolicy::constantName('Relation', $name);

            if (array_key_exists($constantName, $constants) && $constants[$constantName] !== $name) {
                continue;
            }

            $additions[$constantName] = $name;
            $constants[$constantName] = $name;
        }

        $statements = [];
        foreach ($additions as $constantName => $value) {
            $statements[] = new ClassConst(
                [new Const_(new Identifier($constantName), new String_($value))],
                Modifiers::PUBLIC,
            );
        }

        $index = count($node->stmts);
        foreach ($node->stmts as $statementIndex => $statement) {
            if ($statement instanceof ClassMethod) {
                $index = $statementIndex;

                break;
            }
        }

        array_splice($node->stmts, $index, 0, $statements);

        return $additions === [] ? null : $node;
    }

    private function inApplicationNamespace(ClassReflection $class): bool
    {
        foreach ($this->applicationNamespaces as $namespace) {
            if ($class->getName() === $namespace || str_starts_with($class->getName(), rtrim($namespace, '\\') . '\\')) {
                return true;
            }
        }

        return false;
    }

    private function isModelOrModelContract(ClassReflection $class, Scope $scope): bool
    {
        $model = $this->reflectionProvider->getClass(Model::class);

        if ($class->isInterface() === false) {
            return $class->isSubclassOfClass($model);
        }

        if (preg_match('/(?:^|\\\\)Models(?:\\\\|$)/', $class->getName()) === 1) {
            return true;
        }

        $docComment = $class->getNativeReflection()
            ->getDocComment();

        if (! is_string($docComment) || preg_match('/@phpstan-require-extends\\s+([^\\s*]+)/', $docComment, $matches) !== 1) {
            return false;
        }

        $requiredClass = ltrim($matches[1], '\\');

        if ($requiredClass === 'Model') {
            $requiredClass = $scope->resolveName(new Name('Model'));
        }

        return $requiredClass === Model::class;
    }

    private function interfaceDeclaresRelationMethod(Class_ $node, Scope $scope, string $methodName): bool
    {
        foreach ($node->implements as $implementedInterface) {
            $interfaceName = $scope->resolveName($implementedInterface);

            if ($this->reflectionProvider->hasClass($interfaceName) === false) {
                continue;
            }

            $interface = $this->reflectionProvider->getClass($interfaceName);

            if ($interface->hasMethod($methodName) === false) {
                continue;
            }

            $returnType = $interface->getMethod($methodName, $scope)
                ->getVariants()[0]
                ->getReturnType();

            if (IdentifierPolicy::returnsRelation($returnType)) {
                return true;
            }
        }

        $finder = new NodeFinder();
        foreach ($finder->findInstanceOf($this->getFile()->getNewStmts(), Interface_::class) as $interfaceNode) {
            if ($interfaceNode->name === null) {
                continue;
            }

            $isImplemented = false;
            foreach ($node->implements as $implementedInterface) {
                if (str_ends_with($scope->resolveName($implementedInterface), '\\' . $interfaceNode->name->toString())) {
                    $isImplemented = true;

                    break;
                }
            }

            if (! $isImplemented) {
                continue;
            }

            foreach ($interfaceNode->getMethods() as $method) {
                if ($method->name->toString() === $methodName && $method->returnType instanceof Node) {
                    if (IdentifierPolicy::returnsRelation($this->getType($method->returnType))) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
