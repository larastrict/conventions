<?php declare(strict_types=1);

namespace LaraStrict\Conventions\PHPStan;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Controller;
use Livewire\Component;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;

final readonly class InjectionPointRegistry
{
    public const array MethodInjection = [
        Controller::class => ['*'],
        Command::class => ['handle'],
        Seeder::class => ['run'],
        FormRequest::class => ['rules', 'authorize', 'validator', 'after'],
        ShouldQueue::class => ['handle'],
        Component::class => ['boot', 'mount'],
    ];
    public const array NamespaceMethodInjection = [
        '\\Jobs\\' => ['handle'],
    ];
    public const array CallbackInjection = [
        Action::class => ['action', 'url', 'visible', 'hidden', 'disabled'],
        'Filament\\Tables\\Columns\\Column' => ['formatStateUsing', 'state', 'getStateUsing', 'color', 'description', 'tooltip'],
        'Filament\\Tables\\Table' => ['modifyQueryUsing'],
    ];

    /**
     * @param array<string> $constructorNamespaces
     * @param array<class-string> $constructorBaseClasses
     * @param array<string> $additionalConstructorNamespaces
     * @param array<class-string> $additionalConstructorBaseClasses
     * @param array<class-string, list<string>> $additionalMethodInjection Base class or interface to injectable method names; * selects public actions.
     * @param array<string, list<string>> $additionalNamespaceMethodInjection Namespace fragment to injectable method names.
     * @param list<string> $functions Fully qualified names of functions called with injected arguments.
     * @param array<class-string, list<string>> $containerCompositionMethods Methods that compose scoped or runtime-selected objects through an injected container.
     * @param list<string> $frameworkUiFacades Exact facade classes permitted in Filament Resources and Livewire components.
     * @param array<class-string, list<string>> $additionalCallbackInjection Evaluated callback setters supplied by other framework integrations.
     */
    public function __construct(
        private array $constructorNamespaces = InjectableClassSelector::Namespaces,
        private array $constructorBaseClasses = InjectableClassSelector::BaseClasses,
        private array $additionalConstructorNamespaces = [],
        private array $additionalConstructorBaseClasses = [],
        private array $additionalMethodInjection = [],
        private array $additionalNamespaceMethodInjection = [],
        private array $functions = [],
        private array $containerCompositionMethods = [],
        private array $frameworkUiFacades = [Filament::class, 'Filament\\Support\\Facades\\FilamentView'],
        private array $additionalCallbackInjection = [],
    ) {}

    public function isFrameworkUi(?ClassReflection $class): bool
    {
        return $class instanceof ClassReflection && (isset($class->getAncestors()[Component::class]) || isset($class->getAncestors()[Resource::class]));
    }

    public function isStaticHelper(Scope $scope): bool
    {
        $class = $scope->getClassReflection();
        $method = $scope->getFunction();

        return $class instanceof ClassReflection && $method instanceof MethodReflection && $method->isStatic()
            && (str_contains($class->getName(), '\\Helpers\\') || str_ends_with($class->getName(), 'Helper'))
            && $this->resolutionInjection($scope) !== 'callback';
    }

    public function allowsFrameworkFacade(string $facade, Scope $scope): bool
    {
        return $this->isFrameworkUi($scope->getClassReflection()) && in_array($facade, $this->frameworkUiFacades, true);
    }

    public function allowsContainerComposition(Scope $scope): bool
    {
        $class = $scope->getClassReflection();
        $method = $scope->getFunction();

        if (! $class instanceof ClassReflection || $method instanceof MethodReflection === false || $method->isStatic() || strtolower($method->getName()) === '__construct') {
            return false;
        }

        foreach ($this->containerCompositionMethods as $baseClass => $methods) {
            if (isset($class->getAncestors()[$baseClass]) && in_array(strtolower($method->getName()), array_map(strtolower(...), $methods), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string>|null $namespaces
     * @param array<class-string>|null $baseClasses
     */
    public function supportsConstructor(ClassReflection $class, ?array $namespaces = null, ?array $baseClasses = null): bool
    {
        return InjectableClassSelector::matches(
            $class,
            $namespaces ?? [...$this->constructorNamespaces, ...$this->additionalConstructorNamespaces],
            $baseClasses ?? [...$this->constructorBaseClasses, ...$this->additionalConstructorBaseClasses],
        );
    }

    /**
     * @param array<string>|null $constructorNamespaces
     * @param array<class-string>|null $constructorBaseClasses
     */
    public function supportsInjectionMethod(ClassReflection $class, MethodReflection $method, ?array $constructorNamespaces = null, ?array $constructorBaseClasses = null): bool
    {
        if ($method->isStatic()) {
            return false;
        }

        if (strtolower($method->getName()) === '__construct') {
            return $method->isPublic() && $this->supportsConstructor($class, $constructorNamespaces, $constructorBaseClasses);
        }

        return $this->supportsMethod($class, $method);
    }

    /**
     * @return 'constructor'|'method'|'function'|'boot'|'callback'|null
     */
    public function resolutionInjection(Scope $scope): ?string
    {
        if ($scope->isInAnonymousFunction()) {
            $stack = $scope->getFunctionCallStack();
            $caller = array_pop($stack);

            if ($caller instanceof MethodReflection) {
                foreach ([self::CallbackInjection, $this->additionalCallbackInjection] as $map) {
                    foreach ($map as $baseClass => $methods) {
                        if (isset($caller->getDeclaringClass()->getAncestors()[$baseClass]) && in_array(strtolower($caller->getName()), array_map(strtolower(...), $methods), true)) {
                            return 'callback';
                        }
                    }
                }
            }
        }

        $function = $scope->getFunction();

        if (! $function instanceof PhpFunctionFromParserNodeReflection) {
            return null;
        }

        $class = $scope->getClassReflection();

        if ($class instanceof ClassReflection && $function instanceof MethodReflection) {
            if ($function->isStatic()) {
                return null;
            }

            if ($this->supportsMethod($class, $function)) {
                return 'method';
            }

            if (isset($class->getAncestors()[Component::class])) {
                return 'boot';
            }

            return $this->supportsConstructor($class) ? 'constructor' : null;
        }

        foreach ($this->functions as $name) {
            if (strcasecmp(ltrim($name, '\\'), $function->getName()) === 0) {
                return 'function';
            }
        }

        return null;
    }

    private function supportsMethod(ClassReflection $class, MethodReflection $method): bool
    {
        if (InjectableClassSelector::isValueType($class) && isset($class->getAncestors()[FormRequest::class]) === false) {
            return false;
        }

        $isListener = str_contains($class->getName(), '\\Listeners\\') || str_ends_with($class->getName(), 'Listener');

        if (InjectableClassSelector::isHttpController($class) && $this->matchesMethod($method, ['*'])) {
            return true;
        }

        foreach ([self::MethodInjection, $this->additionalMethodInjection] as $map) {
            foreach ($map as $baseClass => $methods) {
                if ($baseClass === ShouldQueue::class && $isListener) {
                    continue;
                }

                if (isset($class->getAncestors()[$baseClass]) && $this->matchesMethod($method, $methods)) {
                    return true;
                }
            }
        }

        foreach ([self::NamespaceMethodInjection, $this->additionalNamespaceMethodInjection] as $map) {
            foreach ($map as $namespace => $methods) {
                if (str_contains($class->getName(), $namespace) && $this->matchesMethod($method, $methods)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param list<string> $methods
     */
    private function matchesMethod(MethodReflection $method, array $methods): bool
    {
        if ($method->isStatic()) {
            return false;
        }

        $name = strtolower($method->getName());

        foreach ($methods as $supported) {
            if (strtolower($supported) === $name || ($supported === '*' && $method->isPublic() && ($name === '__invoke' || str_starts_with($name, '__') === false))) {
                return true;
            }
        }

        return false;
    }
}
