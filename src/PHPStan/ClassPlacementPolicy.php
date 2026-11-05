<?php declare(strict_types=1);

namespace LaraStrict\Conventions\PHPStan;

use Filament\PanelProvider;
use Illuminate\Database\Eloquent\Factories\Factory as ModelFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope as EloquentScope;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use LaraStrict\Conventions\Architecture\CapabilityNamespace;
use LaraStrict\Conventions\Architecture\HttpNamespace;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Type\ObjectType;

/**
 * Keep class names, file names, and namespace paths consistent.
 * Check that class suffixes match their role and location in the application.
 */
final class ClassPlacementPolicy
{
    use ApplicationNamespaceMatcher;

    public const array NamespaceBySuffix = [
        'Action' => [['Actions']],
        'Service' => [['Services']],
        'Factory' => [['Factories']],
        'Builder' => [['Queries', 'Builders'], ['Builders']],
        'ServiceProvider' => [[]],
        'Entity' => [['Entities']],
        'Query' => [['Queries']],
        'Scope' => [['Models']],
        'Transformer' => [['Transformers']],
        'Controller' => [['Http', 'Controllers']],
        'Resource' => [['Http', 'Resources']],
        'Request' => [['Http', 'Requests']],
    ];

    /**
     * @param array<string> $applicationNamespaces
     * @param array<string> $excludedClasses Fully qualified class names.
     * @param array<string> $excludedNamespaces Namespace prefixes.
     * @param array<string, list<list<string>>> $namespaceBySuffix Suffix to allowed relative namespace paths.
     * @param list<ClassPlacementExtension> $extensions Additional package placement allowances.
     */
    public function __construct(
        private array $applicationNamespaces = ['App\\'],
        private array $excludedClasses = [],
        private array $excludedNamespaces = [],
        private array $namespaceBySuffix = self::NamespaceBySuffix,
        private bool $enabled = true,
        private array $extensions = [],
    ) {
    }

    /**
     * @return list<ClassConventionViolation>
     */
    public function check(Class_ $node, string $file, ?string $typeClassName = null): array
    {
        if ($this->enabled === false) {
            return [];
        }

        if (! $node->namespacedName instanceof Name) {
            return [];
        }

        $className = $node->namespacedName->toString();
        $typeClassName ??= $className;
        $applicationPrefix = $this->matchingApplicationNamespacePrefix($className, $this->applicationNamespaces);

        if ($applicationPrefix === null || in_array($className, $this->excludedClasses, true)) {
            return [];
        }

        foreach ($this->excludedNamespaces as $excludedNamespace) {
            $prefix = rtrim($excludedNamespace, '\\') . '\\';

            if (str_starts_with($className, $prefix)) {
                return [];
            }
        }

        $separator = strrpos($className, '\\');

        if ($separator === false) {
            return [];
        }

        $namespace = substr($className, 0, $separator);
        $shortName = substr($className, $separator + 1);

        // Keep class names and file names aligned for ordinary named classes.
        if (basename($file, '.php') !== $shortName) {
            return [
                new ClassConventionViolation(sprintf('Class %s must be declared in a file named %s.php.', $shortName, $shortName), 'larastrict.naming.classFileName', $node->getStartLine(), $className),
            ];
        }

        $relativeNamespace = substr($namespace, strlen($applicationPrefix));
        $actualSegments = $relativeNamespace === '' ? [] : explode('\\', $relativeNamespace);
        $isEloquentScope = (new ObjectType(EloquentScope::class))->isSuperTypeOf(new ObjectType($typeClassName))->yes();

        if ($node->isAbstract() === false && str_ends_with($shortName, 'Model') && $node->extends instanceof Name && (new ObjectType(Model::class))->isSuperTypeOf(new ObjectType($typeClassName))->yes()) {
            return [new ClassConventionViolation('Eloquent Model class names must not end with "Model"; use the Models namespace.', 'larastrict.naming.classPlacement', $node->getStartLine(), $namespace . '\\' . substr($shortName, 0, -5))];
        }

        if ($node->isAbstract() && $isEloquentScope === false && in_array('Entities', $actualSegments, true) === false && str_ends_with($shortName, 'Entity') === false) {
            return [];
        }

        if ($actualSegments !== [] && str_ends_with(str_replace('\\', '/', dirname($file)) . '/', '/' . implode('/', $actualSegments) . '/') === false) {
            return [new ClassConventionViolation('The file directory must match the declared namespace ' . $namespace . '.', 'larastrict.naming.namespacePath', $node->getStartLine(), $className)];
        }

        if ($actualSegments === ['Providers'] && str_ends_with($shortName, 'ServiceProvider')
            && (new ObjectType(ServiceProvider::class))->isSuperTypeOf(new ObjectType($typeClassName))->yes()) {
            return [];
        }

        if (str_ends_with($shortName, 'ServiceProvider') && end($actualSegments) === 'Filament'
            && (new ObjectType(PanelProvider::class))->isSuperTypeOf(new ObjectType($typeClassName))
                ->yes()) {
            return [];
        }

        if ($isEloquentScope) {
            if (in_array('Models', $actualSegments, true) === false) {
                return [new ClassConventionViolation(sprintf('Eloquent scope %s must be placed in Models or a Models subnamespace.', $shortName), 'larastrict.naming.classPlacement', $node->getStartLine(), $this->placementTarget($applicationPrefix, $actualSegments, str_ends_with($shortName, 'Scope') ? $shortName : $shortName . 'Scope', [['Models']]))];
            }

            if (str_ends_with($shortName, 'Scope') === false) {
                return [new ClassConventionViolation(sprintf('Eloquent scope %s must end with "Scope".', $shortName), 'larastrict.naming.classPlacement', $node->getStartLine(), $className . 'Scope')];
            }

            return [];
        }

        if (CapabilityNamespace::contains($actualSegments)
            && (str_ends_with($shortName, 'ServiceProvider') === false || $this->isProviderRoot($actualSegments, $shortName))) {
            return [];
        }

        // Conversion names describe their source and target, not a DTO's role.
        if (in_array('Transformers', $actualSegments, true) && preg_match('/^.+To[A-Z]/', $shortName) === 1) {
            return [];
        }

        $isHttpEndpoint = HttpNamespace::isEndpoint($actualSegments);
        $isModelFactory = str_ends_with($shortName, 'Factory') && $node->extends instanceof Name && (new ObjectType(ModelFactory::class))->isSuperTypeOf(new ObjectType($typeClassName))->yes();

        foreach ($this->namespaceBySuffix as $suffix => $allowedPaths) {
            if (str_ends_with($shortName, $suffix) === false) {
                continue;
            }

            // Framework UI resources are not HTTP serializers.
            if ($suffix === 'Resource' && in_array('Http', $actualSegments, true) === false && (! $node->extends instanceof Name || (new ObjectType(JsonResource::class))->isSuperTypeOf(new ObjectType($typeClassName))->yes() === false)) {
                continue;
            }

            if ($isHttpEndpoint && in_array($suffix, ['Controller', 'Resource', 'Request', 'Entity', 'Action', 'Query'], true)) {
                return [];
            }

            if ($suffix === 'Controller' && HttpNamespace::isControllerNamespace($actualSegments)) {
                return [];
            }

            if ($suffix === 'Factory' && $isModelFactory && in_array('Models', $actualSegments, true)) {
                return [];
            }

            $additionalPaths = [];

            foreach ($this->extensions as $extension) {
                foreach ($extension->getAllowedNamespacePaths($suffix) as $path) {
                    $additionalPaths[] = $path;

                    if ($this->containsPath($actualSegments, $path)) {
                        return [];
                    }
                }
            }

            foreach ($allowedPaths as $path) {
                if (($suffix === 'ServiceProvider' && $path === [] && $this->isProviderRoot($actualSegments, $shortName)) || $this->containsPath($actualSegments, $path)) {
                    return [];
                }
            }

            $expected = array_map(
                static fn (array $path): string => $path === [] ? '<application/domain root>' : implode('\\', $path),
                [...$allowedPaths, ...$additionalPaths],
            );

            if (in_array($suffix, ['Controller', 'Resource', 'Request', 'Entity', 'Action', 'Query'], true)) {
                $expected[] = 'Http\\<area>\\<endpoint>';
            }

            if ($suffix === 'Controller') {
                $expected[] = 'Http\\<area>';
            }

            if ($isModelFactory) {
                $expected[] = 'Models';
            }

            return [
                new ClassConventionViolation(sprintf(
                    'Class %s must be placed in %s because its name ends with "%s".',
                    $shortName,
                    implode(' or ', $expected),
                    $suffix,
                ), 'larastrict.naming.classPlacement', $node->getStartLine(), $this->placementTarget($applicationPrefix, $actualSegments, $shortName, [...$allowedPaths, ...$additionalPaths])),
            ];
        }

        // Enforce the reverse namespace-to-suffix requirement too. Provider
        // placement is handled separately, including global Laravel providers.
        foreach ($this->namespaceBySuffix as $suffix => $paths) {
            if ($suffix === 'ServiceProvider' || $suffix === 'Scope') {
                continue;
            }

            foreach ($paths as $path) {
                if ($path === []) {
                    continue;
                }

                if ($suffix === 'Query' && in_array('Builders', $actualSegments, true)) {
                    continue;
                }

                if ($this->containsPath($actualSegments, $path) && str_ends_with($shortName, $suffix) === false) {
                    return [
                        new ClassConventionViolation(sprintf(
                            'Class %s in namespace segment %s must end with "%s".' . ($suffix === 'Transformer' ? ' Alternatively, use a SourceToTarget conversion name.' : ''),
                            $shortName,
                            implode('\\', $path),
                            $suffix,
                        ), 'larastrict.naming.classPlacement', $node->getStartLine(), $className . $suffix),
                    ];
                }
            }
        }

        if ($this->containsPath($actualSegments, ['Models', 'Scopes'])) {
            return [new ClassConventionViolation(sprintf('Class %s in namespace segment Models\\Scopes must end with "Scope".', $shortName), 'larastrict.naming.classPlacement', $node->getStartLine(), $className . 'Scope')];
        }

        if ($this->containsModelCapabilityPath($actualSegments) && str_ends_with($shortName, 'Scope') === false) {
            return [
                new ClassConventionViolation(sprintf(
                    'Class %s in a Models/HasX capability namespace must end with "Scope".',
                    $shortName,
                ), 'larastrict.naming.classPlacement', $node->getStartLine(), $className . 'Scope'),
            ];
        }

        return [];
    }

    /**
     * @param list<string> $segments
     * @param list<list<string>> $paths
     */
    private function placementTarget(string $prefix, array $segments, string $shortName, array $paths): ?string
    {
        if (count($paths) !== 1 || $paths[0] === [] || in_array('*', $paths[0], true)) {
            return null;
        }

        $domain = [];
        $roles = ['Actions', 'Services', 'Factories', 'Queries', 'Builders', 'Providers', 'Entities', 'Models', 'Scopes', 'Transformers', 'Http'];

        foreach ($segments as $segment) {
            if (in_array($segment, $roles, true)) {
                break;
            }

            $domain[] = $segment;
        }

        return $prefix . implode('\\', [...$domain, ...$paths[0], $shortName]);
    }

    /**
     * @param list<string> $segments
     */
    private function isProviderRoot(array $segments, string $className): bool
    {
        if ($segments === []) {
            return true;
        }

        foreach (['Actions', 'Services', 'Factories', 'Queries', 'Builders', 'Providers', 'Entities', 'Models', 'Scopes', 'Transformers'] as $reserved) {
            if (in_array($reserved, $segments, true)) {
                return false;
            }
        }

        $domain = end($segments);

        if ($domain . 'ServiceProvider' === $className) {
            return true;
        }

        $parent = $segments[count($segments) - 2] ?? null;

        return $parent !== null && $parent !== 'Capabilities'
            && Str::singular($domain) . Str::singular($parent) . 'ServiceProvider' === $className;
    }

    /**
     * @param list<string> $actual
     * @param list<string> $expected
     */
    private function containsPath(array $actual, array $expected): bool
    {
        if ($expected === []) {
            return $actual === [];
        }

        for ($index = 0; $index <= count($actual) - count($expected); $index++) {
            $matches = true;

            foreach ($expected as $offset => $segment) {
                if ($segment !== '*' && $actual[$index + $offset] !== $segment) {
                    $matches = false;

                    break;
                }
            }

            if ($matches) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $segments
     */
    private function containsModelCapabilityPath(array $segments): bool
    {
        foreach ($segments as $index => $segment) {
            if ($segment !== 'Models') {
                continue;
            }

            return isset($segments[$index + 1]) && str_starts_with($segments[$index + 1], 'Has');
        }

        return false;
    }
}
