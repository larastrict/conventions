<?php declare(strict_types=1);

namespace LaraStrict\Conventions\PHPStan;

use Filament\Resources\Resource;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Factories\Factory as ModelFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;
use LaraStrict\Conventions\Architecture\CapabilityNamespace;
use LaraStrict\Conventions\Architecture\HttpNamespace;
use Livewire\Component;
use PHPStan\Reflection\ClassReflection;
use Throwable;
use UnitEnum;

final class InjectableClassSelector
{
    public const array Namespaces = ['\\Actions\\', '\\Contracts\\', '\\Services\\', '\\Queries\\', '\\Transformers\\', '\\Controllers\\', '\\Listeners\\', '\\Managers\\', '\\Policies\\', '\\Middlewares\\', '\\Middleware\\', '\\Factories\\', '\\Resolvers\\', '\\Builders\\'];
    public const array BaseClasses = [Command::class, Seeder::class, Controller::class];

    /**
     * @param array<string> $namespaces
     * @param array<class-string> $baseClasses
     */
    public static function matches(ClassReflection $class, array $namespaces, array $baseClasses): bool
    {
        if (isset($class->getAncestors()[Component::class]) || isset($class->getAncestors()[Resource::class])) {
            return false;
        }

        if (self::isValueType($class)) {
            return false;
        }

        $segments = explode('\\', $class->getName());
        $shortName = array_pop($segments);
        $endpointRole = HttpNamespace::endpointOperationRole($segments, $shortName);
        $capabilityRole = CapabilityNamespace::componentRole($segments, $shortName);

        foreach ($namespaces as $namespace) {
            if (str_contains($class->getName(), $namespace)) {
                return true;
            }

            if (trim($namespace, '\\') === 'Controllers' && self::isHttpController($class)) {
                return true;
            }

            if ($capabilityRole !== null && trim($namespace, '\\') === $capabilityRole) {
                return true;
            }

            if ($endpointRole !== null && trim($namespace, '\\') === $endpointRole) {
                return true;
            }
        }

        foreach ($class->getAncestors() as $ancestor) {
            if (in_array($ancestor->getName(), $baseClasses, true)) {
                return true;
            }
        }

        return false;
    }

    public static function isValueType(ClassReflection $class): bool
    {
        foreach ([Model::class, ModelFactory::class, EloquentBuilder::class, QueryBuilder::class, FormRequest::class, JsonResource::class, UnitEnum::class, Throwable::class] as $baseClass) {
            if (isset($class->getAncestors()[$baseClass])) {
                return true;
            }
        }

        $segments = explode('\\', $class->getName());
        array_pop($segments);

        return array_intersect($segments, ['Entities', 'Models', 'Enums', 'ValueObjects', 'DTOs']) !== [];
    }

    public static function isHttpController(ClassReflection $class): bool
    {
        $segments = explode('\\', $class->getName());
        $shortName = array_pop($segments);

        return str_ends_with($shortName, 'Controller') && (in_array('Controllers', $segments, true) || HttpNamespace::isControllerNamespace($segments));
    }
}
