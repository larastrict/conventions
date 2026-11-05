<?php declare(strict_types=1);

namespace LaraStrict\Conventions\Rector;

use FilesystemIterator;
use LaraStrict\Conventions\PHPStan\InjectionPointRegistry;
use LaraStrict\Conventions\PHPStan\ParameterNamePolicy;
use PhpParser\Comment\Doc;
use PhpParser\Error;
use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Rector\AbstractRector;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Rename injectable collaborator parameters to the names reported by ParameterNameRule.
 */
final class InjectableParameterNameRector extends AbstractRector implements ConfigurableRectorInterface
{
    private const array ForwardingMethods = ['__call', '__callstatic', '__invoke', 'call', 'invoke', 'invokeargs', 'newinstance', 'newinstanceargs'];

    /**
     * @var array<string, bool>
     */
    private static array $externalNameUseCache = [];

    private ParameterNamePolicy $parameterNamePolicy;
    private readonly InjectionPointRegistry $injectionPointRegistry;
    private readonly NodeFinder $nodeFinder;

    /**
     * @var list<string>
     */
    private array $applicationNamespaces = ['App\\'];

    /**
     * @var list<string>
     */
    private array $excludedNamespaces = [];

    /**
     * @var list<string>
     */
    private array $componentNamespaces = ['LaraStrict\\'];

    /**
     * @var list<string>
     */
    private array $componentNamespaceSegments = ParameterNamePolicy::ComponentNamespaceSegments;

    /**
     * @var list<class-string>
     */
    private array $excludedTypes = [];

    /**
     * @var list<string>|null
     */
    private ?array $injectableNamespaces = null;

    /**
     * @var list<class-string>|null
     */
    private ?array $injectableBaseClasses = null;

    /**
     * @var list<string>
     */
    private array $additionalComponentNamespaces = [];

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        ?InjectionPointRegistry $injectionPointRegistry = null,
    )
    {
        $this->injectionPointRegistry = $injectionPointRegistry ?? new InjectionPointRegistry();
        $this->nodeFinder = new NodeFinder();
        $this->buildParameterNamePolicy();
    }

    /**
     * @param array<string, mixed> $configuration
     */
    public function configure(array $configuration): void
    {
        $this->applicationNamespaces = $this->configuredStringList($configuration, 'applicationNamespaces', $this->applicationNamespaces);
        $this->excludedNamespaces = $this->configuredStringList($configuration, 'excludedNamespaces', $this->excludedNamespaces);
        $this->componentNamespaces = $this->configuredStringList($configuration, 'componentNamespaces', $this->componentNamespaces);
        $this->componentNamespaceSegments = $this->configuredStringList($configuration, 'componentNamespaceSegments', $this->componentNamespaceSegments);
        $this->additionalComponentNamespaces = $this->configuredStringList($configuration, 'additionalComponentNamespaces', $this->additionalComponentNamespaces);

        if (array_key_exists('excludedTypes', $configuration)) {
            $this->excludedTypes = $this->configuredClassStringList($configuration['excludedTypes']) ?? $this->excludedTypes;
        }

        if (array_key_exists('injectableNamespaces', $configuration)) {
            $this->injectableNamespaces = $configuration['injectableNamespaces'] === null
                ? null
                : $this->configuredStringList($configuration, 'injectableNamespaces', []);
        }

        if (array_key_exists('injectableBaseClasses', $configuration)) {
            $this->injectableBaseClasses = $configuration['injectableBaseClasses'] === null
                ? null
                : $this->configuredClassStringList($configuration['injectableBaseClasses']);
        }

        $this->buildParameterNamePolicy();
    }

    /**
     * @param array<string, mixed> $configuration
     * @param list<string> $default
     * @return list<string>
     */
    private function configuredStringList(array $configuration, string $key, array $default): array
    {
        if (! array_key_exists($key, $configuration)) {
            return $default;
        }

        $value = $configuration[$key];

        if (! is_array($value) || ! array_is_list($value)) {
            return $default;
        }

        $strings = [];
        foreach ($value as $item) {
            if (! is_string($item)) {
                return $default;
            }

            $strings[] = $item;
        }

        return $strings;
    }

    /**
     * @return list<class-string>|null
     */
    private function configuredClassStringList(mixed $value): ?array
    {
        if (! is_array($value) || ! array_is_list($value)) {
            return null;
        }

        $classNames = [];
        foreach ($value as $className) {
            if (! is_string($className) || (! class_exists($className) && ! interface_exists($className))) {
                return null;
            }

            $classNames[] = $className;
        }

        return $classNames;
    }

    private function buildParameterNamePolicy(): void
    {
        $this->parameterNamePolicy = new ParameterNamePolicy(
            $this->reflectionProvider,
            $this->applicationNamespaces,
            $this->componentNamespaces,
            $this->componentNamespaceSegments,
            $this->excludedTypes,
            $this->additionalComponentNamespaces,
        );
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Name injected collaborators after their component type at supported injection points.', [new CodeSample(
            'public function __construct(private SaveAction $action) {}',
            'public function __construct(private SaveAction $saveAction) {}',
        )]);
    }

    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $node instanceof ClassMethod || ! $node->isPublic()) {
            return null;
        }

        if ($node->stmts === null) {
            return null;
        }

        $statements = array_values($node->stmts);

        $scope = $node->getAttribute(AttributeKey::SCOPE);

        if (! $scope instanceof Scope) {
            return null;
        }

        $class = $scope->getClassReflection();

        if (! $class instanceof ClassReflection || $class->isInterface() || $class->isAbstract() || $class->isAnonymous() || ! $class->hasNativeMethod($node->name->toString())) {
            return null;
        }

        $method = $class->getNativeMethod($node->name->toString());

        $namespace = $scope->getNamespace();

        if ($namespace === null || $this->matchingPrefix($class->getName(), $this->applicationNamespaces) === null) {
            return null;
        }

        foreach ($this->excludedNamespaces as $excludedNamespace) {
            if (str_starts_with($namespace . '\\', rtrim($excludedNamespace, '\\') . '\\')) {
                return null;
            }
        }

        if (! $this->injectionPointRegistry->supportsInjectionMethod($class, $method, $this->injectableNamespaces, $this->injectableBaseClasses)) {
            return null;
        }

        $inheritedCount = $this->inheritedParameterCount($class->getName(), $method);
        $changed = false;

        foreach ($node->params as $position => $param) {
            if ($position < $inheritedCount || ! $param->var instanceof Variable || ! is_string($param->var->name) || ! $this->canRenameParameter($param)) {
                continue;
            }

            $typeNode = ParameterNamePolicy::namedObjectTypeNode($param->type);

            if (! $typeNode instanceof Name) {
                continue;
            }

            $typeName = $scope->resolveName($typeNode);

            if ($this->parameterNamePolicy->isExcludedType($typeName) || ! $this->parameterNamePolicy->isComponentType($typeName)) {
                continue;
            }

            $expectedName = $this->parameterNamePolicy->expectedParameterName($param, $typeName);
            $currentName = $param->var->name;

            if ($currentName === $expectedName || str_ends_with($currentName, ucfirst($expectedName))) {
                continue;
            }

            $classNode = $param->isPromoted() ? $this->findContainingClass($node) : null;

            if (($param->isPromoted() && (! $classNode instanceof Class_ || ! $this->canSafelyRenamePrivatePropertyUses($classNode, $currentName)))
                || $this->hasTargetNameCollision($node, $statements, $classNode, $expectedName)
                || $this->hasDynamicScopeHelper($statements)
                || $this->nodeFinder->findInstanceOf($statements, ClassLike::class) !== []
                || ($param->isPromoted() && ($class->hasInstanceProperty($expectedName) || $class->hasStaticProperty($expectedName)))) {
                continue;
            }

            // Do not rewrite a parameter when local references may depend on its current name.
            if ($this->hasProjectWideNameUse($currentName, $class->getName(), $method->getName()) || $this->hasDynamicVariableReference($statements)) {
                continue;
            }

            // A same-name nested parameter shadows the outer variable. Skip the method instead of guessing.
            if ($this->hasShadowingParameter($statements, $currentName)) {
                continue;
            }

            $param->var->name = $expectedName;
            foreach ($this->nodeFinder->findInstanceOf($statements, Variable::class) as $variable) {
                if (is_string($variable->name) && $variable->name === $currentName) {
                    $variable->name = $expectedName;
                }
            }

            if ($param->isPromoted()) {
                $this->renamePrivatePropertyUses($classNode, $currentName, $expectedName);
            }

            if ($node->getDocComment() instanceof Doc) {
                $docComment = $node->getDocComment()
                    ->getText();
                $updatedDocComment = preg_replace('/(@param\\s+[^\\s*]+\\s+)\\$' . preg_quote($currentName, '/') . '\\b/', '$1$' . $expectedName, $docComment);

                if ($updatedDocComment !== null && $updatedDocComment !== $docComment) {
                    $node->setDocComment(new Doc($updatedDocComment));
                }
            }

            $changed = true;
        }

        return $changed ? $node : null;
    }

    private function canRenameParameter(Param $param): bool
    {
        return $param->flags === 0 || ($param->isPromoted() && ($param->flags & Modifiers::VISIBILITY_MASK) === Modifiers::PRIVATE && ($param->flags & (Modifiers::PUBLIC | Modifiers::PROTECTED)) === 0);
    }

    private function hasProjectWideNameUse(string $name, string $className, string $methodName): bool
    {
        $path = $this->getFile()
            ->getFilePath();
        $root = $this->findProjectRoot($path);
        $cacheKey = ($root ?? $path) . ':' . $className . ':' . $methodName . ':' . $name;

        if (isset(self::$externalNameUseCache[$cacheKey])) {
            return self::$externalNameUseCache[$cacheKey];
        }

        if ($root === null) {
            return self::$externalNameUseCache[$cacheKey] = true;
        }

        $paths = array_unique([...$this->phpFilesIn($root), $path]);
        $namedArgumentPattern = '/(?<![A-Za-z0-9_])' . preg_quote($name, '/') . '\\s*:/';
        $bindingPattern = '/[\"\\\']\\$' . preg_quote($name, '/') . '[\"\\\']/';

        foreach ($paths as $candidatePath) {
            $contents = file_get_contents($candidatePath);

            if ($contents === false) {
                return self::$externalNameUseCache[$cacheKey] = true;
            }

            if ((preg_match($namedArgumentPattern, $contents) === 1 && $this->hasRelevantNamedArgument($contents, $name, $className, $methodName)) || preg_match($bindingPattern, $contents) === 1 || preg_match('/->(?:getProperty|property)\s*\(\s*["\']' . preg_quote($name, '/') . '["\']/', $contents) === 1) {
                return self::$externalNameUseCache[$cacheKey] = true;
            }
        }

        return self::$externalNameUseCache[$cacheKey] = false;
    }

    private function hasRelevantNamedArgument(string $contents, string $name, string $className, string $methodName): bool
    {
        try {
            $nodes = (new ParserFactory())->createForHostVersion()
                ->parse($contents) ?? [];
            $traverser = new NodeTraverser(new NameResolver());
            $nodes = $traverser->traverse($nodes);
        } catch (Error) {
            return true;
        }

        foreach ($this->nodeFinder->findInstanceOf($nodes, CallLike::class) as $call) {
            if ($call->isFirstClassCallable()) {
                continue;
            }

            foreach ($call->getArgs() as $argument) {
                if ($argument->name?->toString() !== $name) {
                    continue;
                }

                // A named argument belongs to its called signature, not to every
                // method or constructor with a parameter of the same name.
                if ($call instanceof New_ && strtolower($methodName) !== '__construct') {
                    continue;
                }

                if (($call instanceof MethodCall || $call instanceof StaticCall) && $call->name instanceof Identifier) {
                    $calledMethod = strtolower($call->name->toString());

                    if ($calledMethod !== strtolower($methodName) && ! in_array($calledMethod, self::ForwardingMethods, true)) {
                        continue;
                    }
                }

                // Same-name methods, dynamic calls, and forwarding APIs stay
                // guarded. Constructors can also exclude known unrelated types.
                if (strtolower($methodName) === '__construct' && $call instanceof New_
                    && $call->class instanceof FullyQualified
                    && $this->reflectionProvider->hasClass($call->class->toString())) {
                    $target = $this->reflectionProvider->getClass($call->class->toString());

                    if ($target->getName() !== $className && ! $target->isSubclassOfClass($this->reflectionProvider->getClass($className))) {
                        continue;
                    }
                }

                return true;
            }
        }

        return false;
    }

    private function findContainingClass(ClassMethod $method): ?Class_
    {
        $classNode = $this->nodeFinder->findFirst(
            $this->getFile()
                ->getNewStmts(),
            fn (Node $candidate): bool => $candidate instanceof Class_ && $this->nodeFinder->findFirst(
                $candidate->stmts,
                static fn (Node $member): bool => $member === $method,
            ) instanceof Node,
        );

        return $classNode instanceof Class_ ? $classNode : null;
    }

    /**
     * @param list<Node\Stmt> $statements
     */
    private function hasTargetNameCollision(ClassMethod $method, array $statements, ?Class_ $classNode, string $name): bool
    {
        foreach ($method->params as $parameter) {
            if ($parameter->var instanceof Variable && $parameter->var->name === $name) {
                return true;
            }
        }

        foreach ($this->nodeFinder->findInstanceOf($statements, Variable::class) as $variable) {
            if ($variable->name === $name) {
                return true;
            }
        }

        if ($classNode instanceof Class_) {
            foreach ($this->nodeFinder->findInstanceOf($classNode->stmts, ClassMethod::class) as $classMethod) {
                foreach ($classMethod->params as $parameter) {
                    if ($parameter->isPromoted() && $parameter->var instanceof Variable && $parameter->var->name === $name) {
                        return true;
                    }
                }
            }

            foreach ($this->nodeFinder->findInstanceOf($classNode->stmts, Property::class) as $property) {
                foreach ($property->props as $propertyItem) {
                    if ($propertyItem->name->toString() === $name) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function renamePrivatePropertyUses(?Class_ $classNode, string $oldName, string $newName): void
    {
        if (! $classNode instanceof Class_ || $this->nodeFinder->findFirst(
            $classNode->stmts,
            static fn (Node $candidate): bool => $candidate instanceof New_ && $candidate->class instanceof Class_,
        ) instanceof Node) {
            return;
        }

        foreach ($classNode->stmts as $member) {
            if (! $member instanceof ClassMethod || $member->stmts === null) {
                continue;
            }

            foreach ($this->nodeFinder->findInstanceOf($member->stmts, PropertyFetch::class) as $propertyFetch) {
                if ($propertyFetch->var instanceof Variable && $propertyFetch->var->name === 'this' && $propertyFetch->name instanceof Identifier && $propertyFetch->name->toString() === $oldName) {
                    $propertyFetch->name = new Identifier($newName);
                }
            }
        }
    }

    private function canSafelyRenamePrivatePropertyUses(Class_ $classNode, string $oldName): bool
    {
        if ($this->nodeFinder->findInstanceOf($classNode->stmts, TraitUse::class) !== [] || $this->nodeFinder->findFirst(
            $classNode->stmts,
            static fn (Node $candidate): bool => $candidate instanceof New_ && $candidate->class instanceof Class_,
        ) instanceof Node) {
            return false;
        }

        foreach ($this->nodeFinder->findInstanceOf($classNode->stmts, PropertyFetch::class) as $propertyFetch) {
            if (! $propertyFetch->name instanceof Identifier || $propertyFetch->name->toString() !== $oldName) {
                if ($propertyFetch->var instanceof Variable && $propertyFetch->var->name === 'this' && ! $propertyFetch->name instanceof Identifier) {
                    return false;
                }

                continue;
            }

            if (! $propertyFetch->var instanceof Variable || $propertyFetch->var->name !== 'this') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<Node\Stmt> $statements
     */
    private function hasDynamicScopeHelper(array $statements): bool
    {
        foreach ($this->nodeFinder->findInstanceOf($statements, FuncCall::class) as $functionCall) {
            if ($functionCall->name instanceof Name && in_array(strtolower($functionCall->name->toString()), ['compact', 'get_defined_vars', 'extract'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<Node\Stmt> $statements
     */
    private function hasDynamicVariableReference(array $statements): bool
    {
        foreach ($this->nodeFinder->findInstanceOf($statements, Variable::class) as $variable) {
            if (! is_string($variable->name)) {
                return true;
            }
        }

        return false;
    }

    private function findProjectRoot(string $path): ?string
    {
        $directory = is_dir($path) ? $path : dirname($path);
        while ($directory !== dirname($directory)) {
            if (is_file($directory . '/composer.json')) {
                return $directory;
            }

            $directory = dirname($directory);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function phpFilesIn(string $directory): array
    {
        $paths = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
                static fn (SplFileInfo $fileInfo): bool => $fileInfo->isDir() === false || ! in_array($fileInfo->getFilename(), ['.git', 'vendor', 'node_modules', 'storage', 'bootstrap/cache'], true),
            ),
        );

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo instanceof SplFileInfo) {
                continue;
            }

            if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {
                $paths[] = $fileInfo->getPathname();
            }
        }

        return $paths;
    }

    /**
     * @param list<Node\Stmt> $statements
     */
    private function hasShadowingParameter(array $statements, string $name): bool
    {
        foreach ($this->nodeFinder->findInstanceOf($statements, FunctionLike::class) as $functionLike) {
            if ($functionLike instanceof ClassMethod) {
                continue;
            }

            foreach ($functionLike->getParams() as $param) {
                if ($param->var instanceof Variable && $param->var->name === $name) {
                    return true;
                }
            }
        }

        return false;
    }

    private function inheritedParameterCount(string $className, MethodReflection $method): int
    {
        $prototype = $method->getPrototype()
            ->getDeclaringClass();

        if ($prototype->getName() === $className || ! $prototype->hasNativeMethod($method->getName())) {
            return 0;
        }

        return count($prototype->getNativeMethod($method->getName())->getVariants()[0]->getParameters());
    }

    /**
     * @param array<string> $namespaces
     */
    private function matchingPrefix(string $className, array $namespaces): ?string
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
