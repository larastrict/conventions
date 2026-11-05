<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\ECS;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\EnumCase;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class ClassMemberOrderingTest extends TestCase
{
    public function testSharedEcsOrdersMembersAndScopesExecutePriority(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'ecs-members-');
        self::assertNotFalse($path);

        try {
            file_put_contents($path, <<<'PHP'
<?php
namespace App\Orders\Actions {
    trait SharedBehavior {}
    final class SaveAction {
        private function helper() {}
        /** Execute the workflow. */
        #[\Deprecated('Keep this attribute')]
        public function execute() { return new class { public function helper() {} public function execute() {} }; }
        protected string $first;
        public function __construct(private readonly string $promoted) {}
        private static string $firstStatic;
        public string $second;
        public static string $secondStatic;
        private const Key = 'value';
        use SharedBehavior;
        public static function other() { return self::class; }
    }
    abstract class AbstractAction {
        protected function helper() {}
        abstract public function execute();
        public function __construct() {}
    }
    trait ExampleAction {
        public function helper() {}
        public function execute() {}
    }
    interface ExampleQuery {
        public function helper();
        public function execute();
    }
    enum StateAction {
        case Ready;
        public function helper() {}
        public function execute() {}
    }
}
namespace App\Orders\Http\Api\ReadOrder {
    final class ReadOrderQuery {
        private function helper() {}
        public function execute() {}
        public function __construct() {}
    }
}
namespace LaraStrict\Database\Actions {
    final class RunAction {
        protected function helper() {}
        public function execute() {}
    }
}
namespace App\Orders\Services {
    final class OrderService {
        private function helper() {}
        public function execute() {}
        public function __construct() {}
    }
}
namespace External\Actions {
    final class ExternalAction {
        private function helper() {}
        public function execute() {}
        public function __construct() {}
    }
}
PHP);
            $root = dirname(__DIR__, 2);
            $process = new Process([
                $root . '/vendor/bin/ecs', 'check', '--fix',
                '--config=' . $root . '/extension-ecs.php', '--paths=' . $path,
            ], $root);
            $process->mustRun();
            $formatted = file_get_contents($path);
            self::assertIsString($formatted);
            $nodes = (new ParserFactory())->createForNewestSupportedVersion()->parse($formatted);
            self::assertNotNull($nodes);
            $classes = (new NodeFinder())->findInstanceOf($nodes, ClassLike::class);
            $members = [];

            foreach ($classes as $class) {
                $name = $class->name?->toString() ?? 'anonymous';
                $members[$name] = array_map(static fn (Node $member): string => match (true) {
                    $member instanceof ClassMethod => $member->name->toString(),
                    $member instanceof Property => $member->props[0]->name->toString(),
                    $member instanceof ClassConst => $member->consts[0]->name->toString(),
                    $member instanceof TraitUse => 'trait',
                    $member instanceof EnumCase => $member->name->toString(),
                    default => $member->getType(),
                }, $class->stmts);

                if ($name === 'SaveAction') {
                    $execute = $class->getMethod('execute');
                    self::assertNotNull($execute);
                    self::assertStringContainsString('Execute the workflow.', $execute->getDocComment()?->getText() ?? '');
                    self::assertSame('Deprecated', $execute->attrGroups[0]->attrs[0]->name->toString());
                }
            }

            self::assertSame([
                'SharedBehavior' => [],
                'SaveAction' => ['trait', 'Key', 'firstStatic', 'secondStatic', 'first', 'second', '__construct', 'execute', 'helper', 'other'],
                'anonymous' => ['helper', 'execute'],
                'AbstractAction' => ['__construct', 'helper', 'execute'],
                'ExampleAction' => ['helper', 'execute'],
                'ExampleQuery' => ['helper', 'execute'],
                'StateAction' => ['Ready', 'helper', 'execute'],
                'ReadOrderQuery' => ['__construct', 'execute', 'helper'],
                'RunAction' => ['execute', 'helper'],
                'OrderService' => ['__construct', 'helper', 'execute'],
                'ExternalAction' => ['__construct', 'helper', 'execute'],
            ], $members);
            self::assertStringContainsString('private readonly string $promoted', $formatted);
            $process->mustRun();
            self::assertSame($formatted, file_get_contents($path));
        } finally {
            unlink($path);
        }
    }
}
