<?php declare(strict_types=1);

namespace LaraStrict\Conventions\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Keep class names, file names, and namespace paths consistent.
 * Check that class suffixes match their role and location in the application.
 *
 * @implements Rule<Class_>
 */
final readonly class ClassPlacementRule implements Rule
{
    private ClassPlacementPolicy $policy;

    /**
     * @param array<string> $applicationNamespaces
     * @param array<string> $excludedClasses Fully qualified class names.
     * @param array<string> $excludedNamespaces Namespace prefixes.
     * @param array<string, list<list<string>>> $namespaceBySuffix Suffix to allowed relative namespace paths.
     * @param list<ClassPlacementExtension> $extensions Additional package placement allowances.
     */
    public function __construct(
        array $applicationNamespaces = ['App\\'],
        array $excludedClasses = [],
        array $excludedNamespaces = [],
        array $namespaceBySuffix = ClassPlacementPolicy::NamespaceBySuffix,
        bool $enabled = true,
        array $extensions = [],
    ) {
        $this->policy = new ClassPlacementPolicy($applicationNamespaces, $excludedClasses, $excludedNamespaces, $namespaceBySuffix, $enabled, $extensions);
    }

    public function getNodeType(): string
    {
        return Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        return array_map(
            static fn (ClassConventionViolation $violation) => RuleErrorBuilder::message($violation->message)
                ->identifier($violation->identifier)
                ->line($violation->line)
                ->build(),
            $this->policy->check($node, $scope->getFile()),
        );
    }
}
