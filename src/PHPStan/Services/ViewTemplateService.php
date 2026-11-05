<?php declare(strict_types=1);

namespace LaraStrict\Conventions\PHPStan\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use LaraStrict\Conventions\Architecture\HttpNamespace;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\InlineHTML;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Throwable;

final class ViewTemplateService
{
    private ?BladeCompiler $compiler = null;
    private ?Parser $parser = null;

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly ParserFactory $parserFactory,
    ) {}

    /**
     * @return list<array{identifier: string, message: string}>
     */
    public function execute(string $source): array
    {
        $compiler = $this->compiler ??= $this->createCompiler();
        $parser = $this->parser ??= $this->parserFactory->createForNewestSupportedVersion();

        try {
            $nodes = $parser->parse($compiler->compileString($source)) ?? [];
            $finder = new NodeFinder();
            $bindings = [];
            foreach ($finder->findInstanceOf($nodes, InlineHTML::class) as $html) {
                preg_match_all('/(?<![:\w]):[A-Za-z_][\w:.-]*\s*=\s*([\'"])(.*?)\1/s', $html->value, $matches);
                foreach ($matches[2] as $expression) {
                    array_push($bindings, ...($parser->parse('<?php ' . html_entity_decode($expression, ENT_QUOTES | ENT_HTML5) . ';') ?? []));
                }
            }

            $nodes = [...$nodes, ...$bindings];
        } catch (Error $error) {
            $this->compiler = null;

            return [[
                'identifier' => 'larastrict.views.syntax',
                'message' => 'Cannot inspect compiled Blade PHP: ' . $error->getRawMessage(),
            ]];
        } catch (Throwable $error) {
            $this->compiler = null;

            return [[
                'identifier' => 'larastrict.views.compile',
                'message' => 'Cannot compile Blade for inspection: ' . $error->getMessage(),
            ]];
        }

        $usesData = $finder->findFirst($nodes, static fn (Node $node): bool => $node instanceof Variable && $node->name === 'data') instanceof Node;

        if ($usesData === false) {
            return [];
        }

        $hasType = false;
        foreach ($finder->find($nodes, static fn (Node $node): bool => $node->getComments() !== []) as $node) {
            foreach ($node->getComments() as $comment) {
                if (preg_match('/@var\s+([^\s]+)\s+\$data\b/', $comment->getText(), $match) === 1
                    && $this->isEntityClassName($match[1])) {
                    $hasType = true;
                }
            }
        }

        $errors = [];

        if ($hasType === false) {
            $errors[] = [
                'identifier' => 'larastrict.views.dataType',
                'message' => 'Declare $data with @var and its domain *Entity class type.',
            ];
        }

        if ($finder->findFirst($nodes, static fn (Node $node): bool => $node instanceof ArrayDimFetch && $node->var instanceof Variable && $node->var->name === 'data') instanceof Node) {
            $errors[] = [
                'identifier' => 'larastrict.views.arrayAccess',
                'message' => 'Use $data->property instead of $data[...] for view data.',
            ];
        }

        return $errors;
    }

    private function createCompiler(): BladeCompiler
    {
        $compiler = new BladeCompiler($this->filesystem, sys_get_temp_dir());
        // Inspect templates without resolving or executing application components.
        $compiler->withoutComponentTags();

        return $compiler;
    }

    private function isEntityClassName(string $type): bool
    {
        $segments = explode('\\', ltrim($type, '\\'));

        if (str_ends_with($type, 'Entity') === false || (in_array('Entities', $segments, true) === false && HttpNamespace::isEndpoint(array_slice($segments, 0, -1)) === false)) {
            return false;
        }

        foreach ($segments as $segment) {
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $segment) !== 1) {
                return false;
            }
        }

        return true;
    }
}
