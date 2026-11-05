<?php declare(strict_types=1);

namespace LaraStrict\Conventions\PHPStan;

use PhpParser\Node;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\Rule;

/**
 * Track convention sources through PHPStan's native result-cache dependencies.
 *
 * @implements Rule<FileNode>
 */
final readonly class ConventionsSourceDependencyRule implements Rule
{
    /**
     * @param list<string> $sourceDirectories
     */
    public function __construct(
        private array $sourceDirectories = [__DIR__, __DIR__ . '/../Architecture'],
    ) {}

    public function getNodeType(): string
    {
        return FileNode::class;
    }

    /**
     * @param Scope&DependencyTracker $scope
     */
    public function processNode(Node $node, Scope $scope): array
    {
        foreach ($this->sourceDirectories as $directory) {
            $scope->trackDirectoryDependency($directory, '*.php');
        }

        return [];
    }
}
