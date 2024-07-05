<?php

declare(strict_types=1);

namespace LaraStrict\Conventions\PHPStan;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Interface_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ClassLike>
 */
final class UsableInContainerRule implements Rule
{
    /**
     * @var array<string>
     */
    private readonly array $namespaces;

    /**
     * @var array<class-string<object>, int>
     */
    private readonly array $extendsMap;

    /**
     * @var array<string, int>
     */
    private readonly array $excludeSuffixesMap;

    /**
     * @param array<string>               $namespaces
     * @param array<string>               $appendNamespaces
     * @param array<string>               $excludeNamespaces
     * @param array<class-string<object>> $extends check classes that extend these classes
     * @param array<string>               $excludeFolders
     * @param array<string>               $excludeSuffixes
     * @param array<string>               $excludeClasses
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        array $namespaces = [],
        array $appendNamespaces = [],
        array $excludeNamespaces = [],
        array $extends = [],
        private readonly array $excludeFolders = [],
        array $excludeSuffixes = [],
        private readonly array $excludeClasses = [],
        private readonly bool $enabled = false,
    ) {
        $excludeMap = array_flip($excludeNamespaces);

        $allowedNamespaces = [];
        self::appendNamespaces(excludeMap: $excludeMap, namespaces: $allowedNamespaces, source: $namespaces);
        self::appendNamespaces(excludeMap: $excludeMap, namespaces: $allowedNamespaces, source: $appendNamespaces);
        $this->namespaces = $allowedNamespaces;
        $this->extendsMap = array_flip($extends);
        $this->excludeSuffixesMap = array_flip($excludeSuffixes);
    }

    public function getNodeType(): string
    {
        return ClassLike::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->enabled === false) {
            return [];
        }

        $isInterface = $node instanceof Interface_;
        $isClass = $node instanceof Class_;
        if ($isClass === false && $isInterface === false) {
            return [];
        }

        $namespace = $node->namespacedName?->toString();

        if ($namespace === null) {
            return [];
        }

        /** @var class-string|null $className */
        $className = $node->namespacedName?->toString();
        if ($className === null) {
            return [];
        }

        $continue = $this->isClassForDI(scope: $scope, node: $node, namespace: $namespace, className: $className);

        if ($continue === false) {
            return [];
        }

        try {
            Container::getInstance()->make($className);

            return [];
        } catch (BindingResolutionException $bindingResolutionException) {
            $error = RuleErrorBuilder::message($bindingResolutionException->getMessage())
                ->identifier('larstrict.usable_in_container');

            if ($isInterface) {
                $error->addTip(
                    'Did you forget to register your interface in the container? Use service providers to register your interfaces.',
                );
                $error->addTip(
                    'If your interface is not meant to be used in the container, move your interface to "Interfaces" namespace.',
                );
            }

            return [$error->build()];
        }
    }

    /**
     * @param array<string, int> $excludeMap
     * @param array<string>      $namespaces
     * @param array<string>      $source source namespaces to append to $namespaces
     */
    private static function appendNamespaces(array $excludeMap, array &$namespaces, array $source): void
    {
        foreach ($source as $namespace) {
            if (array_key_exists($namespace, $excludeMap) === false) {
                $namespaces[] = $namespace;
            }
        }
    }

    /**
     * - If condition returns true, then the class is not for DI.
     * - If resolver returns true, then the class is for DI.
     *
     * @param class-string $className
     */
    private function isClassForDI(Scope $scope, Node $node, string $namespace, string $className): bool {
        $conditions = [
            static fn (): bool => $node instanceof Class_ && $node->isAbstract(),
            fn (): bool => $this->isInExcludedFolderCondition($scope->getFile()),
            fn (): bool => $this->hasExcludedSuffix($scope->getFile()),
            fn (): bool => $this->isExcludedClass($className),
        ];

        foreach ($conditions as $resolver) {
            if ($resolver()) {
                return false;
            }
        }

        $resolvers = [
            fn (): bool => $this->isInNamespaceResolver($namespace),
            fn (): bool => $this->implementsClassResolver($className),
        ];

        foreach ($resolvers as $resolver) {
            if ($resolver()) {
                return true;
            }
        }

        return false;
    }

    private function isInNamespaceResolver(string $namespace): bool
    {
        foreach ($this->namespaces as $usableNamespace) {
            if (str_contains($namespace, $usableNamespace)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param class-string $className
     */
    private function implementsClassResolver(string $className): bool
    {
        if ($this->extendsMap === []) {
            return false;
        }

        if ($this->reflectionProvider->hasClass($className) === false) {
            return false;
        }

        $reflectionClass = $this->reflectionProvider->getClass($className);

        while ($reflectionClass = $reflectionClass->getParentClass()) {
            $name = $reflectionClass->getName();
            if (array_key_exists($name, $this->extendsMap)) {
                return true;
            }
        }

        return false;
    }

    private function isInExcludedFolderCondition(string $file): bool
    {
        foreach ($this->excludeFolders as $excludeFolder) {
            if (str_contains($file, $excludeFolder)) {
                return true;
            }
        }

        return false;
    }

    private function hasExcludedSuffix(string $file): bool
    {
        foreach (array_keys($this->excludeSuffixesMap) as $suffix) {
            if (str_ends_with($file, $suffix . '.php')) {
                return true;
            }
        }

        return false;
    }

    private function isExcludedClass(string $className): bool
    {
        foreach ($this->excludeClasses as $classNameToCheck) {
            if (str_contains($className, $classNameToCheck)) {
                return true;
            }
        }

        return false;
    }
}
