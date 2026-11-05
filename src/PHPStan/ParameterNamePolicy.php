<?php declare(strict_types=1);

namespace LaraStrict\Conventions\PHPStan;

use Carbon\Factory as CarbonFactory;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\UnionType;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Shared collaborator selection and naming policy for analysis and refactoring.
 */
final readonly class ParameterNamePolicy
{
    public const array ComponentNamespaceSegments = ['Contracts', 'Interfaces', 'Services', 'Actions', 'Transformers', 'Builders', 'Queries', 'Factories', 'Managers', 'Resolvers', 'Policies', 'Middleware', 'Middlewares', 'Listeners', 'Controllers'];

    /**
     * @param array<string> $applicationNamespaces
     * @param array<string> $componentNamespaces
     * @param array<string> $componentNamespaceSegments
     * @param array<class-string> $excludedTypes
     * @param array<string> $additionalComponentNamespaces
     */
    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private array $applicationNamespaces = ['App\\'],
        private array $componentNamespaces = ['LaraStrict\\'],
        private array $componentNamespaceSegments = self::ComponentNamespaceSegments,
        private array $excludedTypes = [],
        private array $additionalComponentNamespaces = [],
    ) {}

    public function isComponentType(string $className): bool
    {
        if ($className === CarbonFactory::class) {
            return true;
        }

        if ($this->matchingNamespacePrefix($className, [...$this->applicationNamespaces, ...$this->componentNamespaces, ...$this->additionalComponentNamespaces]) === null || $this->reflectionProvider->hasClass($className) === false) {
            return false;
        }

        $class = $this->reflectionProvider->getClass($className);
        $namespaces = array_map(static fn (string $segment): string => '\\' . $segment . '\\', $this->componentNamespaceSegments);

        return InjectableClassSelector::matches($class, $namespaces, []);
    }

    public function isExcludedType(string $className): bool
    {
        return in_array($className, $this->excludedTypes, true);
    }

    public function expectedParameterName(Param $param, string $className): string
    {
        $namespaceSegments = explode('\\', $className);
        $resolvedShortName = array_pop($namespaceSegments);
        $typeNode = self::namedObjectTypeNode($param->type);
        $originalName = $typeNode?->getAttribute('originalName');

        if ($originalName instanceof Name) {
            $typeNode = $originalName;
        }

        // Keep a local alias when it gives the consuming application a clear name.
        if ($typeNode instanceof Name && count($typeNode->getParts()) === 1 && in_array(strtolower($typeNode->getLast()), ['self', 'parent', 'static'], true) === false) {
            $shortName = $typeNode->getLast();
        } else {
            $shortName = $resolvedShortName;
        }

        $baseName = preg_replace('/(?:Contract|Interface)$/', '', $shortName);

        if ($baseName !== null && $baseName !== '') {
            $shortName = $baseName;
        }

        if (in_array($shortName, ['Factory', 'Service', 'Builder', 'Action', 'Query', 'Transformer'], true) && $namespaceSegments !== []) {
            $shortName = $namespaceSegments[count($namespaceSegments) - 1] . $shortName;
        }

        return lcfirst($shortName);
    }

    public static function namedObjectTypeNode(?Node $type): ?Name
    {
        if ($type instanceof NullableType) {
            return $type->type instanceof Name ? $type->type : null;
        }

        if ($type instanceof UnionType) {
            if (count($type->types) !== 2) {
                return null;
            }

            $name = null;
            $hasNull = false;

            foreach ($type->types as $memberType) {
                if ($memberType instanceof Name) {
                    $name = $memberType;

                    continue;
                }

                if ($memberType instanceof Identifier && strtolower($memberType->toString()) === 'null') {
                    $hasNull = true;

                    continue;
                }

                return null;
            }

            return $hasNull ? $name : null;
        }

        return $type instanceof Name ? $type : null;
    }

    /**
     * @param array<string> $namespaces
     */
    private function matchingNamespacePrefix(string $className, array $namespaces): ?string
    {
        foreach ($namespaces as $namespace) {
            $prefix = rtrim($namespace, '\\') . '\\';

            if (str_starts_with($className, $prefix)) {
                return $prefix;
            }
        }

        return null;
    }
}
