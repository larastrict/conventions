<?php declare(strict_types=1);

namespace LaraStrict\Conventions\Rector\Identifier;

use LaraStrict\Conventions\Architecture\IdentifierPolicy;
use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Replace request key strings with existing or safely generated Key* constants.
 */
final class RequestKeyConstantsRector extends AbstractRector
{
    /**
     * @param list<string> $applicationNamespaces
     */
    public function __construct(
        private readonly array $applicationNamespaces = ['App'],
    ) {}

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Use Key* constants in FormRequest rules and supported input calls.', [
            new CodeSample(
                <<<'CODE'
$this->input('name');
CODE
                ,
                <<<'CODE'
$this->input(self::KeyName);
CODE
                ,
            ),
        ]);
    }

    public function getNodeTypes(): array
    {
        return [Class_::class, Return_::class, MethodCall::class];
    }

    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Class_) {
            return $this->addMissingConstants($node);
        }

        $scope = $node->getAttribute('scope');

        if (! $scope instanceof Scope) {
            return null;
        }

        $class = $scope->getClassReflection();

        if (! $class instanceof ClassReflection || ! $this->isFormRequest($class) || ! $this->inApplicationNamespace($class)) {
            return null;
        }

        if ($node instanceof Return_) {
            if ($scope->getFunction()?->getName() !== 'rules' || $node->expr instanceof Array_ === false) {
                return null;
            }

            $changed = false;
            foreach ($node->expr->items as $item) {
                if ($item->key instanceof String_) {
                    $next = $this->existingKeyFetch($class, $item->key);

                    if ($next instanceof Expr) {
                        $item->key = $next;
                        $changed = true;
                    }
                }
            }

            return $changed ? $node : null;
        }

        if ($node instanceof MethodCall === false || $node->name instanceof Identifier === false || $node->isFirstClassCallable()) {
            return null;
        }

        if ($this->supportedRequestCall($node) === false || $node->getArgs() === []) {
            return null;
        }

        $argumentNode = $this->keyArgument($node, $scope);

        if (! $argumentNode instanceof Arg) {
            return null;
        }

        $argument = $argumentNode->value;

        if ($argument instanceof String_ === false) {
            return null;
        }

        $next = $this->existingKeyFetch($class, $argument);

        if (! $next instanceof Expr) {
            return null;
        }

        $argumentNode->value = $next;

        return $node;
    }

    private function addMissingConstants(Class_ $node): ?Node
    {
        $scope = $node->getAttribute('scope');

        if (! $scope instanceof Scope) {
            return null;
        }

        $class = $scope->getClassReflection();

        if (! $class instanceof ClassReflection || ! $this->isFormRequest($class) || ! $this->inApplicationNamespace($class)) {
            return null;
        }

        $uses = [];
        $finder = new NodeFinder();
        foreach ($node->getMethods() as $method) {
            if ($method->name->toString() === 'rules') {
                foreach ($finder->findInstanceOf($method->stmts ?? [], Return_::class) as $return) {
                    if ($return->expr instanceof Array_ === false) {
                        continue;
                    }

                    foreach ($return->expr->items as $item) {
                        if ($item->key instanceof String_ && IdentifierPolicy::isSupportedRequestKey($item->key->value)) {
                            $uses[] = [$item->key, $item];
                        }
                    }
                }
            }
        }

        foreach ($finder->findInstanceOf($node->stmts, MethodCall::class) as $call) {
            if ($this->supportedRequestCall($call) === false || $call->getArgs() === []) {
                continue;
            }

            $callScope = $call->getAttribute('scope');
            $argument = $callScope instanceof Scope ? $this->keyArgument($call, $callScope) : null;

            if ($argument?->value instanceof String_ && IdentifierPolicy::isSupportedRequestKey($argument->value->value)) {
                $uses[] = [$argument->value, $argument];
            }
        }

        $constants = $class->getNativeReflection()
            ->getConstants();
        foreach ($class->getInterfaces() as $interface) {
            $constants += $interface->getNativeReflection()
                ->getConstants();
        }

        $declaredConstants = $constants;
        $generated = [];
        foreach ($uses as [$literal]) {
            $value = $literal->value;

            if (isset($generated[$value])) {
                continue;
            }

            $existing = IdentifierPolicy::matchingConstantName($constants, $value, 'Key');

            if ($existing !== null) {
                $generated[$value] = $existing;

                continue;
            }

            $name = IdentifierPolicy::constantName('Key', $value);

            if (array_key_exists($name, $constants) && $constants[$name] !== $value) {
                continue;
            }

            $generated[$value] = $name;
            $constants[$name] = $value;
        }

        $added = [];
        $changed = false;
        foreach ($uses as [$literal, $target]) {
            $name = $generated[$literal->value] ?? null;

            if ($name === null) {
                continue;
            }

            $fetch = new ClassConstFetch(new Name('self'), new Identifier($name));

            if ($target instanceof ArrayItem) {
                $target->key = $fetch;
            } elseif ($target instanceof Arg) {
                $target->value = $fetch;
            }

            if (! array_key_exists($name, $declaredConstants)) {
                $added[$name] = $literal->value;
            }

            $changed = true;
        }

        $statements = [];
        foreach ($added as $name => $value) {
            $statements[] = new ClassConst(
                [new Const_(new Identifier($name), new String_($value))],
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

        return $changed ? $node : null;
    }

    private function supportedRequestCall(MethodCall $call): bool
    {
        if ($call->name instanceof Identifier === false || $call->isFirstClassCallable()) {
            return false;
        }

        if (in_array(strtolower($call->name->toString()), ['input', 'query', 'post', 'only', 'except', 'has', 'exists', 'filled', 'missing', 'header'], true) === false) {
            return false;
        }

        $scope = $call->getAttribute('scope');

        return $scope instanceof Scope && IdentifierPolicy::isFormRequest($scope->getType($call->var));
    }

    private function keyArgument(MethodCall $call, Scope $scope): ?Arg
    {
        if ($call->name instanceof Identifier === false) {
            return null;
        }

        $type = $scope->getType($call->var);
        $method = $call->name->toString();

        if ($type->hasMethod($method)->yes() === false) {
            return null;
        }

        foreach ($type->getMethod($method, $scope)->getVariants() as $variant) {
            foreach ($variant->getParameters() as $position => $parameter) {
                if ($parameter->getName() !== 'key') {
                    continue;
                }

                foreach ($call->getArgs() as $index => $argument) {
                    if ($argument->name?->toString() === 'key' || ($argument->name === null && $index === $position)) {
                        return $argument;
                    }
                }
            }
        }

        return null;
    }

    private function existingKeyFetch(ClassReflection $class, String_ $literal): ?Expr
    {
        $constants = $class->getNativeReflection()
            ->getConstants();
        foreach ($class->getInterfaces() as $interface) {
            $constants += $interface->getNativeReflection()
                ->getConstants();
        }

        $name = IdentifierPolicy::matchingConstantName($constants, $literal->value, 'Key');

        return $name === null ? null : new ClassConstFetch(new Name('self'), new Identifier($name));
    }

    private function isFormRequest(?ClassReflection $class): bool
    {
        return $class instanceof ClassReflection && IdentifierPolicy::isFormRequest(new ObjectType($class->getName()));
    }

    private function inApplicationNamespace(?ClassReflection $class): bool
    {
        if (! $class instanceof ClassReflection) {
            return false;
        }

        foreach ($this->applicationNamespaces as $namespace) {
            if ($class->getName() === $namespace || str_starts_with($class->getName(), rtrim($namespace, '\\') . '\\')) {
                return true;
            }
        }

        return false;
    }
}
