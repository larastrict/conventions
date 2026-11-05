<?php declare(strict_types=1);

namespace LaraStrict\Conventions\PHPStan;

trait ApplicationNamespaceMatcher
{
    /**
     * @param array<string> $applicationNamespaces
     */
    private function matchingApplicationNamespacePrefix(string $className, array $applicationNamespaces): ?string
    {
        foreach ($applicationNamespaces as $applicationNamespace) {
            $prefix = rtrim($applicationNamespace, '\\') . '\\';

            if (str_starts_with($className, $prefix)) {
                return $prefix;
            }
        }

        return null;
    }
}
