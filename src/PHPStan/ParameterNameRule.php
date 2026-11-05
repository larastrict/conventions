<?php declare(strict_types=1);

namespace LaraStrict\Conventions\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Name injected collaborators at application injection points.
 *
 * @implements Rule<InClassMethodNode>
 */
final class ParameterNameRule implements Rule
{
    use ApplicationNamespaceMatcher;

    private InjectionPointRegistry $injectionPointRegistry;
    private ParameterNamePolicy $parameterNamePolicy;

    /**
     * @param array<string> $applicationNamespaces
     * @param array<string> $excludedNamespaces
     * @param array<string> $componentNamespaces Owners of checked collaborator types.
     * @param array<string> $additionalComponentNamespaces Package owners added to the checked collaborator types.
     * @param list<string> $componentNamespaceSegments Roles of checked collaborator types.
     * @param array<class-string> $excludedTypes
     * @param array<string>|null $injectableNamespaces Override registry constructor namespace fragments.
     * @param array<class-string>|null $injectableBaseClasses Override registry constructor base types.
     */
    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private array $applicationNamespaces = ['App\\'],
        private array $excludedNamespaces = [],
        private bool $enabled = true,
        private array $componentNamespaces = ['LaraStrict\\'],
        private array $componentNamespaceSegments = ParameterNamePolicy::ComponentNamespaceSegments,
        private array $excludedTypes = [],
        private ?array $injectableNamespaces = null,
        private ?array $injectableBaseClasses = null,
        ?InjectionPointRegistry $injectionPointRegistry = null,
        private array $additionalComponentNamespaces = [],
    ) {
        $this->injectionPointRegistry = $injectionPointRegistry ?? new InjectionPointRegistry();
        $this->parameterNamePolicy = new ParameterNamePolicy(
            $this->reflectionProvider,
            $this->applicationNamespaces,
            $this->componentNamespaces,
            $this->componentNamespaceSegments,
            $this->excludedTypes,
            $this->additionalComponentNamespaces,
        );
    }

    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->enabled === false) {
            return [];
        }

        $class = $node->getClassReflection();
        $namespace = $scope->getNamespace();

        if ($namespace === null || $this->matchingApplicationNamespacePrefix($class->getName(), $this->applicationNamespaces) === null || $class->isInterface() || $class->isAbstract() || $class->isAnonymous()) {
            return [];
        }

        foreach ($this->excludedNamespaces as $excludedNamespace) {
            if (str_starts_with($namespace . '\\', rtrim($excludedNamespace, '\\') . '\\')) {
                return [];
            }
        }

        if ($this->isInjectionMethod($node) === false) {
            return [];
        }

        $inheritedParameterCount = $this->inheritedParameterCount($node);
        $errors = [];

        foreach ($node->getOriginalNode()->params as $position => $param) {
            if ($position < $inheritedParameterCount || $param->var instanceof Variable === false || is_string($param->var->name) === false) {
                continue;
            }

            $typeName = ParameterNamePolicy::namedObjectTypeNode($param->type);

            if (! $typeName instanceof Name) {
                continue;
            }

            $objectClass = $scope->resolveName($typeName);

            if ($this->parameterNamePolicy->isExcludedType($objectClass) || $this->parameterNamePolicy->isComponentType($objectClass) === false) {
                continue;
            }

            $expectedName = $this->parameterNamePolicy->expectedParameterName($param, $objectClass);

            if ($param->var->name === $expectedName || str_ends_with($param->var->name, ucfirst($expectedName))) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Parameter $%s should be named $%s for %s.',
                $param->var->name,
                $expectedName,
                $objectClass,
            ))
                ->identifier('larastrict.naming.parameterName')
                ->line($param->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function isInjectionMethod(InClassMethodNode $node): bool
    {
        return $this->injectionPointRegistry->supportsInjectionMethod(
            $node->getClassReflection(),
            $node->getMethodReflection(),
            $this->injectableNamespaces,
            $this->injectableBaseClasses,
        );
    }

    private function inheritedParameterCount(InClassMethodNode $node): int
    {
        $method = $node->getMethodReflection();
        $prototype = $method->getPrototype()
            ->getDeclaringClass();

        if ($prototype->getName() === $node->getClassReflection()->getName() || $prototype->hasNativeMethod($method->getName()) === false) {
            return 0;
        }

        return count($prototype->getNativeMethod($method->getName())->getVariants()[0]->getParameters());
    }
}
