<?php declare(strict_types=1);

namespace LaraStrict\Conventions\Rector;

use InvalidArgumentException;
use LaraStrict\Conventions\PHPStan\ClassPlacementPolicy;
use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PHPStan\Analyser\Scope;
use Rector\Configuration\RenamedClassesDataCollector;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Rector\AbstractRector;
use Rector\Renaming\NodeManipulator\ClassRenamer;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Rename declarations and references. Move PSR-4 files separately.
 */
final class ClassNamingRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var array<string, string>|null
     */
    private ?array $renames = null;

    private string $root = '';

    /**
     * @var list<string>
     */
    private array $onlyClasses = [];

    private ClassPlacementPolicy $policy;

    public function __construct(
        private readonly ClassNamingPlan $planner,
        private readonly ClassRenamer $classRenamer,
        private readonly RenamedClassesDataCollector $collector,
    ) {
        $this->policy = new ClassPlacementPolicy();
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Apply unambiguous Larastrict class names and namespace placement.', [new CodeSample(
            'class WhereStateIn implements Scope {}',
            'class WhereStateInScope implements Scope {}',
        )]);
    }

    public function getNodeTypes(): array
    {
        return [FullyQualified::class, Name::class, Property::class, FunctionLike::class, Expression::class, ClassLike::class, If_::class, Namespace_::class, String_::class, Use_::class, GroupUse::class];
    }

    public function configure(array $configuration): void
    {
        $this->root = is_string($configuration['projectRoot'] ?? null) ? $configuration['projectRoot'] : '';
        $onlyClasses = $configuration['onlyClasses'] ?? [];

        if (is_array($onlyClasses) && array_is_list($onlyClasses) && array_filter($onlyClasses, is_string(...)) === $onlyClasses) {
            $this->onlyClasses = $onlyClasses;
        }

        if (array_key_exists('policy', $configuration) && ! $configuration['policy'] instanceof ClassPlacementPolicy) {
            throw new InvalidArgumentException('The class naming policy must be a ClassPlacementPolicy instance.');
        }

        if (($configuration['policy'] ?? null) instanceof ClassPlacementPolicy) {
            $this->policy = $configuration['policy'];
        }

        // Explicit maps also let fixture tests cover reference rewriting.
        if (isset($configuration['renames']) && is_array($configuration['renames'])) {
            $this->renames = [];
            foreach ($configuration['renames'] as $old => $new) {
                if (is_string($old) && is_string($new)) {
                    $this->renames[$old] = $new;
                }
            }
        }
    }

    public function refactor(Node $node): Node|array|null
    {
        if ($this->renames === null) {
            $root = $this->root === '' ? getcwd() : realpath($this->root);

            if ($root === false || ! is_file($root . '/composer.json')) {
                return null;
            }

            $plan = $this->planner->build($root, $this->policy, $this->onlyClasses);
            $this->renames = [];
            foreach ($plan as $old => $item) {
                if ($old !== $item['class']) {
                    $this->renames[$old] = $item['class'];
                }
            }
        }

        if ($this->renames === []) {
            return null;
        }

        $this->collector->addOldToNewClasses($this->renames);
        $scope = $node->getAttribute(AttributeKey::SCOPE);

        if ($node instanceof Use_) {
            if ($node->type !== Use_::TYPE_NORMAL) {
                return null;
            }

            $changed = false;
            foreach ($node->uses as $use) {
                $target = $this->renames[$use->name->toString()] ?? null;

                if ($target !== null) {
                    $use->name = new Name($target);
                    $changed = true;
                }
            }

            return $changed ? $node : null;
        }

        if ($node instanceof GroupUse) {
            $uses = [];
            $changed = false;
            foreach ($node->uses as $use) {
                $old = $node->prefix->toString() . '\\' . $use->name->toString();
                $type = $use->type === Use_::TYPE_UNKNOWN ? $node->type : $use->type;
                $target = $type === Use_::TYPE_NORMAL ? ($this->renames[$old] ?? null) : null;
                $uses[] = new Use_([new UseItem(new Name($target ?? $old), $use->alias)], $type);
                $changed = $changed || $target !== null;
            }

            return $changed ? $uses : null;
        }

        if ($node instanceof Namespace_) {
            foreach ($node->stmts as $statement) {
                if ($statement instanceof Class_ && $statement->namespacedName instanceof Name && isset($this->renames[$statement->namespacedName->toString()])) {
                    $target = $this->renames[$statement->namespacedName->toString()];
                    $namespace = substr($target, 0, (int) strrpos($target, '\\'));

                    if ($node->name?->toString() !== $namespace) {
                        $node->name = new Name($namespace);

                        return $node;
                    }
                }
            }

            return null;
        }

        if ($node instanceof Class_ && $node->namespacedName instanceof Name && isset($this->renames[$node->namespacedName->toString()])) {
            $target = $this->renames[$node->namespacedName->toString()];
            $shortName = substr($target, (int) strrpos($target, '\\') + 1);

            if ($node->name?->toString() !== $shortName) {
                $node->name = new Identifier($shortName);

                return $node;
            }
        }

        if ($node instanceof String_ && isset($this->renames[ltrim($node->value, '\\')])) {
            $node->value = (str_starts_with($node->value, '\\') ? '\\' : '') . $this->renames[ltrim($node->value, '\\')];

            return $node;
        }

        return $this->classRenamer->renameNode($node, $this->renames, $scope instanceof Scope ? $scope : null);
    }
}
