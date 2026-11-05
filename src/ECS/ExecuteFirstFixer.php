<?php declare(strict_types=1);

namespace LaraStrict\Conventions\ECS;

use LaraStrict\Conventions\Architecture\ClassMemberOrder;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Analyzer\NamespacesAnalyzer;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;
use SplFileInfo;

/**
 * Keep execute() first among methods in concrete application Actions and Queries.
 * Delegate token ordering to PHP CS Fixer to preserve comments and attributes.
 */
final class ExecuteFirstFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Put execute() after the constructor in concrete App and LaraStrict Actions and Queries.',
            [new CodeSample('<?php namespace App\Orders\Actions; class SaveAction { private function helper() {} public function execute() {} }')],
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_CLASS) && $tokens->isTokenKindFound(T_FUNCTION);
    }

    public function getPriority(): int
    {
        // After OrderedClassElementsFixer (65), before member spacing fixers.
        return 64;
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        $analyzer = new TokensAnalyzer($tokens);
        $namespaces = (new NamespacesAnalyzer())->getDeclarations($tokens);
        $fixer = new OrderedClassElementsFixer();
        $fixer->configure([
            'order' => ClassMemberOrder::ExecuteFirst,
            'sort_algorithm' => 'none',
        ]);

        // Work from the end so nested classes and later namespaces retain their indexes.
        for ($index = count($tokens) - 1; $index > 0; $index--) {
            if (! $tokens[$index]->isGivenKind(T_CLASS) || $analyzer->getClassyModifiers($index)['abstract'] !== null) {
                continue;
            }

            $name = $tokens->getNextMeaningfulToken($index);

            if ($name === null || ! $tokens[$name]->isGivenKind(T_STRING)
                || preg_match('/(?:Action|Query)$/', $tokens[$name]->getContent()) !== 1) {
                continue;
            }

            $application = false;

            foreach ($namespaces as $namespace) {
                if ($namespace->getScopeStartIndex() <= $index && $namespace->getScopeEndIndex() >= $index
                    && preg_match('/^(?:App|LaraStrict)(?:\\\\|$)/', $namespace->getFullName()) === 1) {
                    $application = true;

                    break;
                }
            }

            if (! $application) {
                continue;
            }

            $start = $tokens->getNextTokenOfKind($name, ['{']);

            if ($start === null) {
                continue;
            }

            $end = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_BRACE, $start);
            // A single synthetic outer class lets the native fixer order only its
            // members. Nested classes retain their independently selected order.
            $body = Tokens::fromCode('<?php class LarastrictOrdering ' . $tokens->generatePartialCode($start, $end));
            $fixer->fix($file, $body);

            if ($body->isChanged()) {
                $bodyStart = $body->getNextTokenOfKind(0, ['{']);

                if ($bodyStart === null) {
                    continue;
                }

                $bodyEnd = $body->findBlockEnd(Tokens::BLOCK_TYPE_BRACE, $bodyStart);
                $replacement = [];

                for ($part = $bodyStart; $part <= $bodyEnd; $part++) {
                    $replacement[] = clone $body[$part];
                }

                $tokens->overrideRange($start, $end, $replacement);
            }
        }
    }
}
