<?php declare(strict_types=1);

namespace LaraStrict\Conventions\PHPStan;

/**
 * Add package-specific placement allowances without replacing the core rules.
 */
interface ClassPlacementExtension
{
    /**
     * Paths match consecutive relative namespace segments at any depth.
     * A wildcard (*) matches one required segment. Paths must not be empty.
     * These allowances do not impose a suffix on other classes in those paths.
     *
     * @return list<non-empty-list<string>>
     */
    public function getAllowedNamespacePaths(string $suffix): array;
}
