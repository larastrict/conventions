<?php declare(strict_types=1);

namespace LaraStrict\Conventions\PHPStan;

use LaraStrict\Conventions\Architecture\CapabilityNamespace;
use LaraStrict\Conventions\Architecture\HttpNamespace;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use ReflectionMethod;

/**
 * Keep Actions and Queries focused on one public execute() operation.
 * Allow additional methods required by external interfaces.
 *
 * @implements Rule<Class_>
 */
final class ActionQueryPublicApiRule implements Rule
{
    use ApplicationNamespaceMatcher;

    /**
     * @param array<string> $applicationNamespaces
     */
    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private array $applicationNamespaces = ['App\\'],
        private bool $enabled = true,
    )
    {
    }

    public function getNodeType(): string
    {
        return Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->enabled === false) {
            return [];
        }

        if ($node->namespacedName === null) {
            return [];
        }

        $className = $node->namespacedName->toString();
        $applicationNamespacePrefix = $this->matchingApplicationNamespacePrefix(
            $className,
            $this->applicationNamespaces,
        );

        if ($applicationNamespacePrefix === null) {
            return [];
        }

        $separator = strrpos($className, '\\');

        if ($separator === false) {
            return [];
        }

        $namespace = substr($className, 0, $separator);
        $segments = explode('\\', $namespace);
        $queriesIndex = array_search('Queries', $segments, true);

        $shortName = substr($className, $separator + 1);

        if (in_array('Actions', $segments, true) === false && $queriesIndex === false && HttpNamespace::endpointOperationRole($segments, $shortName) === null && in_array(CapabilityNamespace::componentRole($segments, $shortName), ['Actions', 'Queries'], true) === false) {
            return [];
        }

        if ($queriesIndex !== false && ($segments[$queriesIndex + 1] ?? null) === 'Builders') {
            return [];
        }

        $errors = [];
        $executeIsPublic = false;
        $nativeReflection = null;
        $externalInterfaceMethods = [];

        if ($this->reflectionProvider->hasClass($className)) {
            $nativeReflection = $this->reflectionProvider->getClass($className)
                ->getNativeReflection();
            foreach ($nativeReflection->getInterfaceNames() as $interfaceName) {
                if ($this->matchingApplicationNamespacePrefix($interfaceName, $this->applicationNamespaces) !== null
                    || $this->reflectionProvider->hasClass($interfaceName) === false) {
                    continue;
                }

                $interfaceReflection = $this->reflectionProvider->getClass($interfaceName)
                    ->getNativeReflection();
                foreach ($interfaceReflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    $declaringInterface = $method->getDeclaringClass()
                        ->getName();

                    if ($this->matchingApplicationNamespacePrefix($declaringInterface, $this->applicationNamespaces) !== null) {
                        continue;
                    }

                    $externalInterfaceMethods[strtolower($method->getName())] = true;
                }
            }
        }

        foreach ($node->getMethods() as $method) {
            $name = $method->name->toString();

            if (strcasecmp($name, 'execute') === 0 && $method->isPublic()) {
                $executeIsPublic = true;
            }

            if ($method->isPublic() === false
                || $name === '__construct'
                || strcasecmp($name, 'execute') === 0
                || isset($externalInterfaceMethods[strtolower($name)])) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Action and Query classes may expose only execute() publicly; %s() is also public.',
                $name,
            ))
                ->identifier('larastrict.architecture.publicApi')
                ->line($node->getStartLine())
                ->build();
        }

        if ($nativeReflection !== null) {
            if ($nativeReflection->hasMethod('execute') && $nativeReflection->getMethod('execute')->isPublic()) {
                $executeIsPublic = true;
            }

            foreach ($nativeReflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $name = $method->getName();
                $declaringClass = $method->getDeclaringClass()
                    ->getName();

                if ($declaringClass === $className
                    || $name === '__construct'
                    || strcasecmp($name, 'execute') === 0
                    || isset($externalInterfaceMethods[strtolower($name)])) {
                    continue;
                }

                if (str_starts_with($declaringClass, $applicationNamespacePrefix) === false) {
                    continue;
                }

                $errors[] = RuleErrorBuilder::message(sprintf(
                    'Action and Query classes may expose only execute() publicly; inherited %s() is also public.',
                    $name,
                ))
                    ->identifier('larastrict.architecture.publicApi')
                    ->line($node->getStartLine())
                    ->build();
            }
        }

        if ($node->isAbstract() === false && $executeIsPublic === false) {
            $errors[] = RuleErrorBuilder::message(sprintf(
                'Concrete Action and Query classes must define a public execute() method (%s).',
                $className,
            ))
                ->identifier('larastrict.architecture.executeMethod')
                ->line($node->getStartLine())
                ->build();
        }

        return $errors;
    }
}
