<?php declare(strict_types=1);

namespace LaraStrict\Conventions\PHPStan;

use Illuminate\Contracts\View\Factory;
use LaraStrict\Conventions\Architecture\HttpNamespace;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

/**
 * Require application views to receive a typed Entity under the data key.
 * Check literal namespaced view calls and allow only scalar values alongside that Entity.
 *
 * @implements Rule<Expr>
 */
final readonly class ViewDataRule implements Rule
{
    /**
     * @param list<string> $applicationNamespaces
     * @param list<string> $excludedViewNamespaces
     */
    public function __construct(
        private array $applicationNamespaces = ['App'],
        private array $excludedViewNamespaces = ['errors', 'mail', 'pagination', 'vendor'],
        private bool $enabled = true,
    )
    {
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

        if (! $this->inApplicationNamespace($scope)) {
            return [];
        }

        $viewName = null;
        $data = null;

        if ($node instanceof FuncCall && $node->name instanceof Name && strtolower($scope->resolveName($node->name)) === 'view') {
            $viewName = $this->argument($node->args, 0, 'view');
            $data = $this->argument($node->args, 1, 'data');
        } elseif ($node instanceof MethodCall && $node->name instanceof Identifier && $node->name->toString() === 'make' && $node->args !== [] && (new ObjectType(Factory::class))->isSuperTypeOf($scope->getType($node->var))->yes()) {
            $viewName = $this->argument($node->args, 0, 'view');
            $data = $this->argument($node->args, 1, 'data');
        }

        if (! $viewName instanceof Arg || ! $viewName->value instanceof String_ || ! $this->isApplicationViewName($viewName->value->value)) {
            return [];
        }

        if (! $data instanceof Arg || ! $scope->getType($data->value)->isArray()->yes()) {
            return [$this->error('Application views must receive a data array with a typed Entity under the data key.', 'larastrict.views.dataBoundary')];
        }

        $type = $scope->getType($data->value);
        $valid = true;
        $hasViewEntity = false;
        $hasDataEntity = true;
        $constantArrays = $type->getConstantArrays();

        if ($constantArrays !== []) {
            foreach ($constantArrays as $constantArray) {
                $keys = $constantArray->getKeyTypes();
                $values = $constantArray->getValueTypes();
                $shapeHasDataEntity = false;
                foreach ($values as $index => $valueType) {
                    if (! $this->isAllowedDataValueType($valueType)) {
                        $valid = false;

                        break 2;
                    }

                    $hasViewEntity = $hasViewEntity || $this->containsViewEntity($valueType);
                    $keyStrings = $keys[$index]->getConstantStrings();

                    if (count($keyStrings) === 1 && $keyStrings[0]->getValue() === 'data' && $this->isViewEntityType($valueType)) {
                        $shapeHasDataEntity = true;
                    }
                }

                $hasDataEntity = $hasDataEntity && $shapeHasDataEntity;
            }
        } else {
            $valid = false;
        }

        if (! $valid || ! $hasViewEntity || ! $hasDataEntity) {
            return [$this->error('Application view data must contain a typed Entity from Entities or an HTTP endpoint namespace under the data key; extra values must be scalar.', 'larastrict.views.dataBoundary')];
        }

        return [];
    }

    private function inApplicationNamespace(Scope $scope): bool
    {
        $namespace = $scope->getNamespace();

        if ($namespace === null) {
            return false;
        }

        foreach ($this->applicationNamespaces as $root) {
            $root = trim($root, '\\');

            if ($namespace === $root || str_starts_with($namespace, $root . '\\')) {
                return true;
            }
        }

        return false;
    }

    private function isApplicationViewName(string $name): bool
    {
        if (preg_match('~^([A-Za-z0-9_.-]+)::[A-Za-z0-9_./-]+$~', $name, $matches) !== 1) {
            return false;
        }

        return ! in_array(strtolower($matches[1]), array_map(strtolower(...), $this->excludedViewNamespaces), true);
    }

    /**
     * @param array<int|string, Node\Arg|Node\ArgPlaceholder|Node\VariadicPlaceholder> $args
     */
    private function argument(array $args, int $position, string $name): ?Arg
    {
        foreach ($args as $index => $argument) {
            if (! $argument instanceof Arg) {
                continue;
            }

            if ($argument->name?->toString() === $name || (! $argument->name instanceof Identifier && $index === $position)) {
                return $argument;
            }
        }

        return null;
    }

    private function isViewEntityType(Type $type): bool
    {
        if ($type instanceof UnionType) {
            foreach ($type->getTypes() as $inner) {
                if (! $this->isViewEntityType($inner)) {
                    return false;
                }
            }

            return true;
        }

        if (! $type->isObject()->yes()) {
            return false;
        }

        $classNames = $type->getObjectClassNames();

        if ($classNames === []) {
            return false;
        }

        foreach ($classNames as $className) {
            $namespaceSegments = array_slice(explode('\\', $className), 0, -1);

            if (! str_ends_with($className, 'Entity') || (! str_contains($className, '\\Entities\\') && ! HttpNamespace::isEndpoint($namespaceSegments))) {
                return false;
            }
        }

        return true;
    }

    private function isAllowedDataValueType(Type $type): bool
    {
        if ($type instanceof UnionType) {
            foreach ($type->getTypes() as $inner) {
                if (! $this->isAllowedDataValueType($inner)) {
                    return false;
                }
            }

            return true;
        }

        if ($type->isString()->yes() || $type->isInteger()->yes() || $type->isFloat()->yes() || $type->isBoolean()->yes()) {
            return true;
        }

        return $this->isViewEntityType($type);
    }

    private function containsViewEntity(Type $type): bool
    {
        if ($type instanceof UnionType) {
            foreach ($type->getTypes() as $inner) {
                if ($this->containsViewEntity($inner)) {
                    return true;
                }
            }

            return false;
        }

        return $this->isViewEntityType($type);
    }

    private function error(string $message, string $identifier): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)->identifier($identifier)->build();
    }
}
