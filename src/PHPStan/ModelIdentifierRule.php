<?php declare(strict_types=1);

namespace LaraStrict\Conventions\PHPStan;

use Illuminate\Database\Eloquent\Builder;
use LaraStrict\Conventions\Architecture\IdentifierPolicy;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\TraitUse;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\NeverType;

/**
 * Require constants for model column and relation identifiers in supported Eloquent calls and definitions.
 * Check that relationship methods use snake_case and have matching relation constants.
 *
 * @implements Rule<Node>
 */
final class ModelIdentifierRule implements Rule
{
    use ApplicationNamespaceMatcher;

    /**
     * @param list<string> $applicationNamespaces
     */
    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private array $applicationNamespaces = ['App'],
        private bool $enabled = true,
    ) {}

    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->enabled === false) {
            return [];
        }

        if ($node instanceof Class_) {
            $className = $node->namespacedName?->toString();

            if ($className === null || $this->matchingApplicationNamespacePrefix($className, $this->applicationNamespaces) === null) {
                return [];
            }

            $model = IdentifierPolicy::model($this->reflectionProvider, $className);

            return $model instanceof ClassReflection ? $this->checkDefinition($node, $model, $scope) : [];
        }

        if (($node instanceof MethodCall || $node instanceof StaticCall) === false || $node->name instanceof Identifier === false || $node->isFirstClassCallable()) {
            return [];
        }

        // A selectRaw alias is a result key, not an Eloquent model attribute.
        if ($node instanceof MethodCall && IdentifierPolicy::selectedRawAlias($node) !== null) {
            return [];
        }

        $owner = $scope->getClassReflection();

        if (! $owner instanceof ClassReflection || $this->matchingApplicationNamespacePrefix($owner->getName(), $this->applicationNamespaces) === null) {
            return [];
        }

        $prefix = IdentifierPolicy::callPrefix($node->name->toString());

        if ($prefix === null) {
            return [];
        }

        $argument = $node->getArgs()[0]
            ->value ?? null;

        if ($argument instanceof String_ === false || IdentifierPolicy::isIdentifier($argument->value) === false) {
            return [];
        }

        $models = [];

        if ($node instanceof StaticCall && $node->class instanceof Name) {
            $models = [$scope->resolveName($node->class)];
        } elseif ($node instanceof MethodCall) {
            $receiver = $scope->getType($node->var);
            $models = [...$receiver->getObjectClassNames(), ...$receiver->getTemplateType(Builder::class, 'TModel')->getObjectClassNames()];
        }

        foreach (array_unique($models) as $name) {
            $model = IdentifierPolicy::model($this->reflectionProvider, $name);

            if ($model instanceof ClassReflection) {
                return [$this->literalError($argument, $model, $prefix)];
            }
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkDefinition(Class_ $node, ClassReflection $model, Scope $scope): array
    {
        $errors = [];
        foreach ($node->getProperties() as $property) {
            foreach ($property->props as $item) {
                $name = $item->name->toString();

                if ($item->default instanceof Array_ === false || in_array($name, IdentifierPolicy::MODEL_DEFINITION_PROPERTIES, true) === false) {
                    continue;
                }

                foreach ($item->default->items as $entry) {
                    $identifier = $name === 'casts' ? $entry->key : $entry->value;

                    if ($identifier instanceof String_ && $identifier->value !== '*') {
                        $errors[] = $this->literalError($identifier, $model, 'Attribute');
                    }
                }
            }
        }

        $constants = $model->getNativeReflection()
            ->getConstants();
        foreach ($node->getMethods() as $method) {
            if ($method->isPublic() === false || $method->isStatic() || $method->name->toString() === '__construct') {
                continue;
            }

            $name = $method->name->toString();
            $returnType = $model->getMethod($name, $scope)
                ->getVariants()[0]
                ->getReturnType();

            if ($returnType instanceof NeverType || IdentifierPolicy::returnsRelation($returnType) === false) {
                continue;
            }

            if ($this->isSnakeCase($name) === false) {
                $errors[] = $this->relationMethodNameError($name, $method->getStartLine());
            }

            if ($this->hasRelationConstant($name, $constants) === false) {
                $errors[] = $this->missingRelationConstant($name, $method->getStartLine());
            }
        }

        $traits = [];
        foreach ($model->getTraits() as $trait) {
            $line = $this->traitUseLine($node, $trait->getName());
            $traits[$trait->getName()] = [$trait, $line];
            foreach ($trait->getTraits(true) as $nestedTrait) {
                $traits[$nestedTrait->getName()] = [$nestedTrait, $line];
            }
        }

        foreach ($traits as [$trait, $line]) {
            foreach ($trait->getNativeReflection()->getMethods() as $method) {
                if ($method->isPublic() === false || $method->isStatic() || $method->getDeclaringClass()->getName() !== $trait->getName()) {
                    continue;
                }

                $name = $method->getName();
                $returnType = $trait->getMethod($name, $scope)
                    ->getVariants()[0]
                    ->getReturnType();

                if ($returnType instanceof NeverType || IdentifierPolicy::returnsRelation($returnType) === false) {
                    continue;
                }

                if ($this->isSnakeCase($name) === false) {
                    $errors[] = $this->relationMethodNameError($name, $line);
                }

                if ($this->hasRelationConstant($name, $constants) === false) {
                    $errors[] = $this->missingRelationConstant($name, $line);
                }
            }
        }

        return $errors;
    }

    private function traitUseLine(Class_ $node, string $traitName): int
    {
        foreach ($node->stmts as $statement) {
            if ($statement instanceof TraitUse === false) {
                continue;
            }

            foreach ($statement->traits as $trait) {
                if ($trait->toString() === $traitName) {
                    return $statement->getStartLine();
                }
            }
        }

        return $node->getStartLine();
    }

    private function isSnakeCase(string $name): bool
    {
        return IdentifierPolicy::isSnakeCase($name);
    }

    private function relationMethodNameError(string $name, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message('Relationship method ' . $name . '() must use snake_case.')
            ->identifier('larastrict.models.relationMethodName')
            ->line($line)
            ->build();
    }

    /**
     * @param array<string, mixed> $constants
     */
    private function hasRelationConstant(string $methodName, array $constants): bool
    {
        return IdentifierPolicy::matchingConstantName($constants, $methodName, 'Relation') !== null;
    }

    private function missingRelationConstant(string $methodName, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message('Define a Relation* constant for the ' . $methodName . '() relationship, on the Model or its capability interface.')
            ->identifier('larastrict.models.relationConstant')
            ->line($line)
            ->build();
    }

    private function literalError(String_ $node, ClassReflection $model, string $prefix): IdentifierRuleError
    {
        $constants = $model->getNativeReflection()
            ->getConstants();
        $expected = IdentifierPolicy::matchingConstantName($constants, $node->value, $prefix)
            ?? IdentifierPolicy::constantName($prefix, $node->value);

        return RuleErrorBuilder::message('Use ' . $model->getDisplayName() . '::' . $expected . ' instead of the literal ' . var_export($node->value, true) . '.')
            ->identifier($prefix === 'Relation' ? 'larastrict.models.relationName' : 'larastrict.models.attributeName')
            ->line($node->getStartLine())
            ->build();
    }
}
