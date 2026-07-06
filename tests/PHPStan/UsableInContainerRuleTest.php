<?php

declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan;

use Illuminate\Container\Container;
use LaraStrict\Conventions\PHPStan\UsableInContainerRule;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\UsableInContainer\BaseCommand;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\UsableInContainer\SomeTrait;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<UsableInContainerRule>
 */
final class UsableInContainerRuleTest extends RuleTestCase
{
    use SomeTrait; // just to hide phpstan message

    /**
     * @var array<string>
     */
    private array $namespaces = [];

    /**
     * @var array<string>
     */
    private array $appendNamespaces = [];

    /**
     * @var array<string>
     */
    private array $excludeNamespaces = [];

    /**
     * @var array<class-string<object>>
     */
    private array $extends = [];

    /**
     * @var array<string>
     */
    private array $excludeFolders = [];

    /**
     * @var array<string>
     */
    private array $excludeSuffixes = [];

    /**
     * @var array<string>
     */
    private array $excludeClasses = [];

    private bool $enabled = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset container between tests to avoid cross-test bindings leaking.
        Container::setInstance(new Container());

        $this->namespaces = ['LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\UsableInContainer'];
        $this->appendNamespaces = [];
        $this->excludeNamespaces = [];
        $this->extends = [];
        $this->excludeFolders = [];
        $this->excludeSuffixes = [];
        $this->excludeClasses = [];
        $this->enabled = true;
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }

    protected function getRule(): Rule
    {
        return new UsableInContainerRule(
            reflectionProvider: self::createReflectionProvider(),
            namespaces: $this->namespaces,
            appendNamespaces: $this->appendNamespaces,
            excludeNamespaces: $this->excludeNamespaces,
            extends: $this->extends,
            excludeFolders: $this->excludeFolders,
            excludeSuffixes: $this->excludeSuffixes,
            excludeClasses: $this->excludeClasses,
            enabled: $this->enabled,
        );
    }

    public function testRuleDisabledProducesNoErrors(): void
    {
        $this->enabled = false;

        $this->analyse([__DIR__ . '/Fixtures/UsableInContainer/UnresolvableService.php'], []);
    }

    public function testResolvableClassProducesNoErrors(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/UsableInContainer/ResolvableService.php'], []);
    }

    public function testUnresolvableClassInNamespaceProducesError(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/UsableInContainer/UnresolvableService.php'], [
            [
                'Unresolvable dependency resolving [Parameter #0 [ <required> string $requiredValue ]] in class LaraStrict\ConventionsTests\PHPStan\Fixtures\UsableInContainer\UnresolvableService',
                7,
            ],
        ]);
    }

    public function testUnresolvableInterfaceProducesErrorWithTips(): void
    {
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/Fixtures/UsableInContainer/UnresolvableInterface.php']);

        self::assertCount(1, $errors);
        $tips = $errors[0]->getTip();
        self::assertNotNull($tips);
        self::assertStringContainsString('Did you forget to register your interface', $tips);
        self::assertStringContainsString('"Interfaces" namespace', $tips);
    }

    public function testAbstractClassIsIgnored(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/UsableInContainer/AbstractService.php'], []);
    }

    public function testExcludedClassIsIgnored(): void
    {
        $this->excludeClasses = ['ExcludedByClass'];

        $this->analyse([__DIR__ . '/Fixtures/UsableInContainer/ExcludedByClass.php'], []);
    }

    public function testExcludedNamespaceIsIgnored(): void
    {
        $this->excludeNamespaces = ['LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\UsableInContainer'];

        $this->analyse([__DIR__ . '/Fixtures/UsableInContainer/UnresolvableService.php'], []);
    }

    public function testExcludedSuffixIsIgnored(): void
    {
        $this->excludeSuffixes = ['Contract'];

        $this->analyse([__DIR__ . '/Fixtures/UsableInContainer/ExcludedSuffixContract.php'], []);
    }

    public function testExcludedFolderIsIgnored(): void
    {
        $this->excludeFolders = ['Fixtures/UsableInContainer'];

        $this->analyse([__DIR__ . '/Fixtures/UsableInContainer/UnresolvableService.php'], []);
    }

    public function testAppendNamespacesAreUsed(): void
    {
        $this->namespaces = [];
        $this->appendNamespaces = ['LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\UsableInContainer'];

        $this->analyse([__DIR__ . '/Fixtures/UsableInContainer/UnresolvableService.php'], [
            [
                'Unresolvable dependency resolving [Parameter #0 [ <required> string $requiredValue ]] in class LaraStrict\ConventionsTests\PHPStan\Fixtures\UsableInContainer\UnresolvableService',
                7,
            ],
        ]);
    }

    public function testExtendsResolverDetectsClassOutsideAllowedNamespace(): void
    {
        // The child class is in a different namespace, so it is only picked up via the "extends" resolver.
        $this->namespaces = [];
        $this->extends = [BaseCommand::class];

        $this->analyse([__DIR__ . '/Fixtures/UsableInContainerOther/ChildCommand.php'], [
            [
                'Unresolvable dependency resolving [Parameter #0 [ <required> string $requiredValue ]] in class LaraStrict\ConventionsTests\PHPStan\Fixtures\UsableInContainerOther\ChildCommand',
                9,
            ],
        ]);
    }

    public function testGetNodeTypeReturnsClassLike(): void
    {
        self::assertSame(ClassLike::class, $this->getRule()->getNodeType());
    }

    public function testTraitIsIgnored(): void
    {
        // Edge case: a Trait is a ClassLike node but is neither Class_ nor Interface_,
        // so processNode must return no errors even though the trait namespace matches.
        $this->analyse([__DIR__ . '/Fixtures/UsableInContainer/SomeTrait.php'], []);
    }

    public function testAnonymousClassIsIgnored(): void
    {
        // Edge case: an anonymous class has no namespacedName, so processNode must
        // bail out and produce no errors for the file (only the outer class is checked).
        $this->analyse([__DIR__ . '/Fixtures/UsableInContainer/AnonymousClassHolder.php'], []);
    }

    public function testExtendsResolverIgnoresClassWithoutMatchingParent(): void
    {
        // Edge case: "extends" resolver is active but the class has no parent that
        // matches the extends map, so isClassForDI() returns false and no error is emitted.
        $this->namespaces = [];
        $this->extends = [BaseCommand::class];

        $this->analyse([__DIR__ . '/Fixtures/UsableInContainer/UnresolvableService.php'], []);
    }
}
