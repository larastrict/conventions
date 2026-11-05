<?php declare(strict_types=1);

namespace LaraStrict\Conventions\PHPStan;

use LaraStrict\Conventions\PHPStan\Services\ViewTemplateService;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<FileNode>
 */
final readonly class ViewTemplateRule implements Rule
{
    public function __construct(
        private ViewTemplateService $viewTemplateService,
    ) {}

    public function getNodeType(): string
    {
        return FileNode::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $file = $scope->getFile();

        if (str_ends_with($file, '.blade.php') === false) {
            return [];
        }

        $source = file_get_contents($file);

        if ($source === false) {
            return [];
        }

        $errors = [];

        foreach ($this->viewTemplateService->execute($source) as $error) {
            $errors[] = RuleErrorBuilder::message($error['message'])
                ->identifier($error['identifier'])
                ->build();
        }

        return $errors;
    }
}
