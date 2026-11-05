<?php declare(strict_types=1);

namespace LaraStrict\Conventions\Rector\Identifier;

use Illuminate\Database\Eloquent\Builder;
use LaraStrict\Conventions\Architecture\IdentifierPolicy;
use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Extract selected raw SQL aliases into private constants on their owning class.
 */
final class QueryAliasConstantsRector extends AbstractRector
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
        return new RuleDefinition('Extract selected raw SQL aliases into private constants.', [
            new CodeSample(
                <<<'CODE'
$query->selectRaw("MAX({$table}.created_at) as last_verification_at")->value('last_verification_at');
CODE
                ,
                <<<'CODE'
$query->selectRaw("MAX({$table}.created_at) as " . self::AliasLastVerificationAt)->value(self::AliasLastVerificationAt);
CODE
                ,
            ),
        ]);
    }

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $node instanceof Class_ || ! $node->name instanceof Identifier) {
            return null;
        }

        $scope = $node->getAttribute('scope');

        if (! $scope instanceof Scope) {
            return null;
        }

        $className = $scope->getClassReflection()?->getName();

        if ($className === null || ! $this->inApplicationNamespace($className) || ! $this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $nativeClass = $this->reflectionProvider->getClass($className)->getNativeReflection();
        $allConstants = $nativeClass->getConstants();
        $added = [];
        $changed = false;
        $finder = new NodeFinder();
        foreach ($node->getMethods() as $method) {
            foreach ($finder->findInstanceOf($method->stmts ?? [], MethodCall::class) as $valueCall) {
                $callScope = $valueCall->getAttribute('scope');

                if (! $callScope instanceof Scope || $callScope->getClassReflection()?->getName() !== $className) {
                    continue;
                }

                $details = IdentifierPolicy::selectedRawAliasDetails($valueCall);

                if ($details === null) {
                    continue;
                }

                $value = $valueCall->getArgs()[0]->value ?? null;

                if (! $value instanceof String_ || $value->value !== $details['alias']) {
                    continue;
                }

                $rawCall = $valueCall->var;

                if (! $rawCall instanceof MethodCall || $rawCall->getArgs() === []) {
                    continue;
                }

                $receiverType = $this->getType($rawCall->var);

                if (! (new ObjectType(Builder::class))->isSuperTypeOf($receiverType)->yes()
                    && ! (new ObjectType(\Illuminate\Database\Query\Builder::class))->isSuperTypeOf($receiverType)->yes()) {
                    continue;
                }

                $constantName = IdentifierPolicy::constantName('Alias', $details['alias']);
                $existing = $nativeClass->getReflectionConstant($constantName);

                if ($existing !== false) {
                    if ($existing->getValue() !== $details['alias'] || ! $existing->isPrivate() || $existing->getDeclaringClass()->getName() !== $className) {
                        continue;
                    }
                } elseif (array_key_exists($constantName, $added) && $added[$constantName] === $details['alias']) {
                    // Reuse a constant added for another matching projection in this class.
                } elseif (array_key_exists($constantName, $allConstants) || array_key_exists($constantName, $added)) {
                    continue;
                } else {
                    $added[$constantName] = $details['alias'];
                    $allConstants[$constantName] = $details['alias'];
                }

                $sql = $details['sql'];

                if ($sql instanceof String_) {
                    $parts = [new String_($details['prefix']), $this->selfConstant($constantName)];

                    if ($details['suffix'] !== '') {
                        $parts[] = new String_($details['suffix']);
                    }
                } else {
                    $modifiedSql = clone $sql;
                    $last = $details['partIndex'];

                    if ($last === null || ! $modifiedSql->parts[$last] instanceof InterpolatedStringPart) {
                        unset($added[$constantName]);

                        continue;
                    }

                    $modifiedSql->parts[$last]->value = $details['prefix'];
                    $parts = [$modifiedSql];
                    $parts[] = $this->selfConstant($constantName);

                    if ($details['suffix'] !== '') {
                        $parts[] = new String_($details['suffix']);
                    }
                }

                $sqlExpression = array_shift($parts);
                foreach ($parts as $part) {
                    $sqlExpression = new Concat($sqlExpression, $part);
                }

                $rawCall->getArgs()[0]->value = $sqlExpression;
                $valueCall->getArgs()[0]->value = $this->selfConstant($constantName);
                $changed = true;
            }
        }

        if ($added !== []) {
            $constants = [];
            foreach ($added as $name => $value) {
                $constants[] = new Const_(new Identifier($name), new String_($value));
            }

            $node->stmts = [new ClassConst($constants, Modifiers::PRIVATE, [
                'startLine' => $node->getStartLine(),
                'endLine' => $node->getEndLine(),
            ]), ...$node->stmts];
            $changed = true;
        }

        return $changed ? $node : null;
    }

    private function selfConstant(string $name): ClassConstFetch
    {
        return new ClassConstFetch(new Name('self'), new Identifier($name));
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
