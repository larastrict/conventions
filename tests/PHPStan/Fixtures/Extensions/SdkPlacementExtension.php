<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Extensions;

use LaraStrict\Conventions\PHPStan\ClassPlacementExtension;

final class SdkPlacementExtension implements ClassPlacementExtension
{
    public function getAllowedNamespacePaths(string $suffix): array
    {
        return $suffix === 'Entity' ? [['Sdk', 'Endpoints', '*']] : [];
    }
}
