<?php declare(strict_types=1);

namespace LaraStrict\Conventions\Architecture;

final class HttpNamespace
{
    /**
     * @param list<string> $segments Namespace segments without the class name.
     */
    public static function isEndpoint(array $segments): bool
    {
        return self::matchesArea($segments, 2);
    }

    /**
     * @param list<string> $segments Namespace segments without the class name.
     */
    public static function isControllerNamespace(array $segments): bool
    {
        return self::matchesArea($segments, 1);
    }

    /**
     * @param list<string> $segments Namespace segments without the class name.
     */
    public static function endpointOperationRole(array $segments, string $shortName): ?string
    {
        if (self::isEndpoint($segments) === false) {
            return null;
        }

        foreach ([
            'Action' => 'Actions',
            'Query' => 'Queries',
        ] as $suffix => $role) {
            if (str_ends_with($shortName, $suffix)) {
                return $role;
            }
        }

        return null;
    }

    /**
     * @param list<string> $segments
     */
    private static function matchesArea(array $segments, int $minimumDepth): bool
    {
        $http = array_search('Http', $segments, true);

        if ($http === false) {
            return false;
        }

        $endpoint = array_slice($segments, $http + 1);
        $roleFolders = ['Http', 'Controllers', 'Resources', 'Requests', 'Entities', 'Actions', 'Services', 'Factories', 'Queries', 'Builders', 'Models', 'Scopes', 'Transformers', 'Contracts', 'Interfaces', 'Providers', 'Helpers', 'Exceptions', 'Middleware', 'Middlewares', 'routes'];

        return count($endpoint) >= $minimumDepth && array_intersect($endpoint, $roleFolders) === [];
    }
}
