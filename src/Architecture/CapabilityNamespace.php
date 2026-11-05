<?php declare(strict_types=1);

namespace LaraStrict\Conventions\Architecture;

final class CapabilityNamespace
{
    /**
     * @param list<string> $segments Namespace segments without the class name.
     */
    public static function contains(array $segments): bool
    {
        $index = array_search('Capabilities', $segments, true);

        return $index !== false && $index > 0 && isset($segments[$index + 1]);
    }

    /**
     * @param list<string> $segments Namespace segments without the class name.
     */
    public static function componentRole(array $segments, string $shortName): ?string
    {
        if (self::contains($segments) === false) {
            return null;
        }

        foreach ([
            'Contract' => 'Contracts',
            'Interface' => 'Interfaces',
            'Service' => 'Services',
            'Action' => 'Actions',
            'Query' => 'Queries',
            'Factory' => 'Factories',
            'Builder' => 'Builders',
            'Transformer' => 'Transformers',
            'Controller' => 'Controllers',
            'Manager' => 'Managers',
            'Resolver' => 'Resolvers',
            'Policy' => 'Policies',
            'Middleware' => 'Middleware',
            'Listener' => 'Listeners',
        ] as $suffix => $role) {
            if (str_ends_with($shortName, $suffix)) {
                return $role;
            }
        }

        return null;
    }
}
