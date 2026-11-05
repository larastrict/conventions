<?php declare(strict_types=1);

namespace LaraStrict\Conventions\Rector\Identifier;

use Illuminate\Database\Eloquent\Builder;
use LaraStrict\Conventions\Architecture\IdentifierPolicy;
use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Replace supported Eloquent identifier strings when an exact model constant exists.
 */
final class ModelIdentifierConstantsRector extends AbstractRector
{
    /**
     * @var array<string, array<string, string>>
     */
    private array $generatedAttributes = [];

    /**
     * @param list<string> $applicationNamespaces
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly array $applicationNamespaces = ['App'],
    ) {}

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Use existing model identifier constants in supported Eloquent definitions and calls.', [
            new CodeSample(
                <<<'CODE'
$model->where('status', 'active');
CODE
                ,
                <<<'CODE'
$model->where(Model::AttributeStatus, 'active');
CODE
                ,
            ),
        ]);
    }

    public function getNodeTypes(): array
    {
        return [Class_::class, MethodCall::class, StaticCall::class];
    }

    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Class_) {
            return $this->refactorModelDefinition($node);
        }

        if (($node instanceof MethodCall || $node instanceof StaticCall) === false || $node->name instanceof Identifier === false || $node->isFirstClassCallable() || $node->getArgs() === []) {
            return null;
        }

        // A raw select alias names a projected result, not a model column.
        if ($node instanceof MethodCall && IdentifierPolicy::selectedRawAlias($node) !== null) {
            return null;
        }

        if (array_filter($node->getArgs(), static fn (Arg $argument): bool => $argument->name instanceof Identifier) !== []) {
            return null;
        }

        $prefix = IdentifierPolicy::callPrefix($node->name->toString());

        if ($prefix === null) {
            return null;
        }

        $argumentNode = $node->getArgs()[0];
        $argument = $argumentNode->value;

        if ($argument instanceof String_ === false || IdentifierPolicy::isIdentifier($argument->value) === false) {
            return null;
        }

        foreach ($this->modelsForCall($node) as $model) {
            $constant = $prefix === 'Attribute'
                ? ($this->generatedAttributes[$model->getName()][$argument->value] ?? $this->matchingConstant($model, $argument->value, $prefix))
                : $this->matchingConstant($model, $argument->value, $prefix);

            if ($constant !== null) {
                $argumentNode->value = $this->constantFetch($model->getName(), $constant);

                return $node;
            }
        }

        return null;
    }

    private function refactorModelDefinition(Class_ $node): ?Node
    {
        $scope = $node->getAttribute('scope');
        $class = $scope instanceof Scope ? $scope->getClassReflection() : null;

        if (! $class instanceof ClassReflection || ! $this->inApplicationNamespace($class->getName()) || ! IdentifierPolicy::model($this->reflectionProvider, $class->getName()) instanceof ClassReflection) {
            return null;
        }

        $constants = $class->getNativeReflection()
            ->getConstants();
        /** @var array<string, string> $added */
        $added = [];
        $changed = false;
        foreach ($node->getProperties() as $property) {
            foreach ($property->props as $item) {
                $propertyName = $item->name->toString();

                if (in_array($propertyName, IdentifierPolicy::MUTABLE_ATTRIBUTE_DEFINITION_PROPERTIES, true) === false) {
                    continue;
                }

                if ($item->default instanceof Array_ === false) {
                    continue;
                }

                foreach ($item->default->items as $entry) {
                    if ($propertyName === 'casts') {
                        if ($entry->key instanceof String_) {
                            $next = $this->definitionAttributeFetch($class, $entry->key, $constants, $added);

                            if ($next instanceof Expr) {
                                $entry->key = $next;
                                $changed = true;
                            }
                        }
                    } elseif ($entry->value instanceof String_) {
                        $next = $this->definitionAttributeFetch($class, $entry->value, $constants, $added);

                        if ($next instanceof Expr) {
                            $entry->value = $next;
                            $changed = true;
                        }
                    }
                }
            }
        }

        if ($added !== []) {
            $statements = [];
            foreach ($added as $name => $value) {
                $this->generatedAttributes[$class->getName()][$value] = $name;
                $statements[] = new ClassConst(
                    [new Const_(new Identifier($name), new String_($value))],
                    Modifiers::PUBLIC,
                    [
                        'startLine' => $node->getStartLine(),
                        'endLine' => $node->getEndLine(),
                    ],
                );
            }

            $index = 0;
            foreach ($node->stmts as $statementIndex => $statement) {
                if (! $statement instanceof ClassConst) {
                    $index = $statementIndex;

                    break;
                }

                $index = $statementIndex + 1;
            }

            array_splice($node->stmts, $index, 0, $statements);
        }

        return $changed ? $node : null;
    }

    /**
     * @return list<ClassReflection>
     */
    private function modelsForCall(MethodCall|StaticCall $node): array
    {
        $scope = $node->getAttribute('scope');

        if (! $scope instanceof Scope) {
            return [];
        }

        $names = [];

        if ($node instanceof StaticCall && $node->class instanceof Name && $this->reflectionProvider->hasClass($scope->resolveName($node->class))) {
            $names[] = $scope->resolveName($node->class);
        } elseif ($node instanceof MethodCall) {
            $type = $scope->getType($node->var);
            $names = [...$type->getObjectClassNames(), ...$type->getTemplateType(Builder::class, 'TModel')->getObjectClassNames()];
        }

        $models = [];
        foreach (array_unique($names) as $name) {
            if ($this->reflectionProvider->hasClass($name) === false) {
                continue;
            }

            $model = $this->reflectionProvider->getClass($name);

            if (IdentifierPolicy::model($this->reflectionProvider, $model->getName()) instanceof ClassReflection) {
                if ($this->inApplicationNamespace($model->getName())) {
                    $models[] = $model;
                }
            }
        }

        return $models;
    }

    /**
     * @param array<string, mixed> $constants
     * @param array<string, string> $added
     */
    private function definitionAttributeFetch(ClassReflection $class, String_ $literal, array &$constants, array &$added): ?Expr
    {
        if (IdentifierPolicy::isIdentifier($literal->value) === false) {
            return null;
        }

        $constant = $this->matchingConstant($class, $literal->value, 'Attribute', includeNonPublic: true);

        if ($constant === null) {
            $constant = IdentifierPolicy::constantName('Attribute', $literal->value);

            if (array_key_exists($constant, $constants) && $constants[$constant] !== $literal->value) {
                return null;
            }

            $constants[$constant] = $literal->value;
            $added[$constant] = $literal->value;
        }

        return new ClassConstFetch(new Name('self'), new Identifier($constant));
    }

    private function matchingConstant(ClassReflection $class, string $value, string $prefix, bool $includeNonPublic = false): ?string
    {
        $constants = $class->getNativeReflection()
            ->getConstants();
        foreach ($constants as $name => $constantValue) {
            if ($constantValue === $value && IdentifierPolicy::acceptsConstantName($name, $prefix)) {
                $reflectionConstant = $class->getNativeReflection()
                    ->getReflectionConstant($name);

                if ($reflectionConstant === false || (! $includeNonPublic && ! $reflectionConstant->isPublic())) {
                    continue;
                }

                return $name;
            }
        }

        return null;
    }

    private function constantFetch(string $class, string $constant): Expr
    {
        return new ClassConstFetch(new FullyQualified($class), new Identifier($constant));
    }

    private function inApplicationNamespace(string $className): bool
    {
        foreach ($this->applicationNamespaces as $namespace) {
            if ($className === $namespace || str_starts_with($className, rtrim($namespace, '\\') . '\\')) {
                return true;
            }
        }

        return false;
    }
}
