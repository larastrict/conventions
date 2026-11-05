<?php declare(strict_types=1);

namespace LaraStrict\Conventions\Rector;

use FilesystemIterator;
use LaraStrict\Conventions\PHPStan\ClassPlacementPolicy;
use PhpParser\Error;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PHPStan\Reflection\ReflectionProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/**
 * Discover class corrections from the same policy that produces diagnostics.
 */
final readonly class ClassNamingPlan
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {}

    /**
     * @param list<string> $onlyClasses
     * @return array<string, array{class: string, source: string, destination: string}>
     */
    public function build(string $root, ClassPlacementPolicy $policy, array $onlyClasses = []): array
    {
        $composer = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($composer) || ! is_array($composer['autoload'] ?? null) || ! is_array($composer['autoload']['psr-4'] ?? null)) {
            throw new RuntimeException('Composer autoload.psr-4 is required for class and file renames.');
        }

        $roots = [];
        foreach ($composer['autoload']['psr-4'] as $namespace => $path) {
            if (is_string($namespace) && is_string($path) && $path !== '' && ! str_contains($path, '..') && ! str_starts_with($path, '/')) {
                $directory = realpath($root . '/' . $path);

                if ($directory !== false && str_starts_with($directory . '/', $root . '/')) {
                    $roots[$namespace] = $directory;
                }
            }
        }

        $plan = [];
        $parser = (new ParserFactory())->createForHostVersion();
        $finder = new NodeFinder();
        foreach ($roots as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $entry) {
                if (! $entry instanceof SplFileInfo || ! $entry->isFile() || $entry->getExtension() !== 'php' || $entry->isLink()) {
                    continue;
                }

                $file = $entry->getPathname();

                try {
                    $nodes = $parser->parse((string) file_get_contents($file)) ?? [];
                    $nodes = (new NodeTraverser(new NameResolver()))->traverse($nodes);
                } catch (Error) {
                    continue;
                }

                foreach ($nodes as $statement) {
                    if (! $statement instanceof Namespace_ && ! $statement instanceof Nop
                        && (! $statement instanceof Declare_ || $statement->stmts !== null)) {
                        continue 2;
                    }
                }

                $classes = $finder->findInstanceOf($nodes, ClassLike::class);
                $namespaces = $finder->findInstanceOf($nodes, Namespace_::class);

                if (count($classes) !== 1 || count($namespaces) !== 1 || ! $classes[0] instanceof Class_ || ! $classes[0]->name instanceof Identifier || ! $classes[0]->namespacedName instanceof Name) {
                    continue;
                }

                // Moving a file that also owns functions or runtime statements is ambiguous.
                foreach ($namespaces[0]->stmts as $statement) {
                    if (! $statement instanceof Class_ && ! $statement instanceof Use_ && ! $statement instanceof GroupUse && ! $statement instanceof Nop) {
                        continue 2;
                    }
                }

                $class = $classes[0];
                $old = $class->namespacedName?->toString();

                if ($old === null) {
                    continue;
                }

                if (($onlyClasses !== [] && ! in_array($old, $onlyClasses, true)) || ! $this->reflectionProvider->hasClass($old)) {
                    continue;
                }

                $candidate = clone $class;
                $candidateFile = $file;
                $target = $old;
                $destination = null;
                for ($step = 0; $step < 8; $step++) {
                    $violations = $policy->check($candidate, $candidateFile, $old);

                    if ($violations === []) {
                        break;
                    }

                    $target = $violations[0]->expectedClass;

                    if ($target === null) {
                        continue 2;
                    }

                    $destination = $this->destination($target, $roots);

                    if ($destination === null) {
                        continue 2;
                    }

                    $candidate->namespacedName = new Name($target);
                    $candidate->name = new Identifier(substr($target, (int) strrpos($target, '\\') + 1));
                    $candidateFile = $destination;
                }

                if ($destination === null || $destination === $file || file_exists($destination)
                    || $policy->check($candidate, $candidateFile, $old) !== []
                    || ($target !== $old && $this->reflectionProvider->hasClass($target))) {
                    continue;
                }

                $plan[$old] = [
                    'class' => $target,
                    'source' => $file,
                    'destination' => $destination,
                ];
            }
        }

        // Do not guess when two declarations would occupy the same destination.
        $counts = array_count_values(array_map(static fn (array $item): string => strtolower($item['destination']), $plan));

        return array_filter($plan, static fn (array $item): bool => $counts[strtolower($item['destination'])] === 1);
    }

    /**
     * @param array<string, string> $roots
     */
    private function destination(string $class, array $roots): ?string
    {
        $destination = null;
        $matchedLength = -1;
        foreach ($roots as $prefix => $path) {
            if (str_starts_with($class, $prefix) && strlen($prefix) > $matchedLength) {
                $destination = $path . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
                $matchedLength = strlen($prefix);
            }
        }

        return $destination;
    }
}
