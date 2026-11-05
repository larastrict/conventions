<?php declare(strict_types=1);

namespace LaraStrict\Conventions\PHPStan;

use LaraStrict\Conventions\Architecture\IdentifierPolicy;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Wrkflow\GetValue\GetValue;

/**
 * Require reusable request key constants instead of literal field names.
 * Check FormRequest validation arrays and supported input getter calls.
 *
 * @implements Rule<Node>
 */
final class RequestKeyConstantsRule implements Rule
{
    use ApplicationNamespaceMatcher;

    /**
     * @param list<string> $applicationNamespaces
     */
    public function __construct(
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

        if ($node instanceof Return_ === false && $node instanceof MethodCall === false) {
            return [];
        }

        $class = $scope->getClassReflection();

        if (! $class instanceof ClassReflection || $this->matchingApplicationNamespacePrefix($class->getName(), $this->applicationNamespaces) === null || IdentifierPolicy::isFormRequest(new ObjectType($class->getName())) === false) {
            return [];
        }

        if ($node instanceof Return_) {
            if ($scope->getFunction()?->getName() !== 'rules' || $node->expr instanceof Array_ === false) {
                return [];
            }

            $errors = [];

            foreach ($node->expr->items as $item) {
                if ($item->key !== null) {
                    $errors = [...$errors, ...$this->checkKey($item->key)];
                }
            }

            return $errors;
        }

        if ($node->name instanceof Identifier === false || $node->isFirstClassCallable()) {
            return [];
        }

        $receiver = $scope->getType($node->var);

        if (IdentifierPolicy::isFormRequest($receiver) === false
            && (new ObjectType(GetValue::class))->isSuperTypeOf($receiver)
                ->yes() === false) {
            return [];
        }

        $method = $node->name->toString();

        if ($receiver->hasMethod($method)->yes() === false) {
            return [];
        }

        // Use the reflected key parameter: object getters take the class first.
        foreach ($receiver->getMethod($method, $scope)->getVariants() as $variant) {
            foreach ($variant->getParameters() as $position => $parameter) {
                if ($parameter->getName() !== 'key') {
                    continue;
                }

                foreach ($node->getArgs() as $index => $argument) {
                    if ($argument->name?->toString() === 'key' || ($argument->name === null && $index === $position)) {
                        return $this->checkKey($argument->value);
                    }
                }
            }
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkKey(Expr $key): array
    {
        if ($key instanceof Concat) {
            return [...$this->checkKey($key->left), ...$this->checkKey($key->right)];
        }

        if ($key instanceof Array_) {
            $errors = [];

            foreach ($key->items as $item) {
                $errors = [...$errors, ...$this->checkKey($item->value)];
            }

            return $errors;
        }

        if ($key instanceof String_ === false || IdentifierPolicy::isWildcardRequestKey($key->value)) {
            return [];
        }

        return [RuleErrorBuilder::message(sprintf("Use a request Key* constant instead of the literal key '%s'.", $key->value))
            ->identifier('larastrict.requests.keyConstant')
            ->line($key->getStartLine())
            ->build()];
    }
}
