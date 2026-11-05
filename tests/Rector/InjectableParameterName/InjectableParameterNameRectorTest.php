<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\Rector\InjectableParameterName;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class InjectableParameterNameRectorTest extends AbstractRectorTestCase
{
    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config.php';
    }

    public function testRenamesInjectedConstructorParameters(): void
    {
        $this->doTestFile(__DIR__ . '/fixtures/constructor.php.inc');
    }

    public function testKeepsSemanticDtoNamesAndNamedCallSites(): void
    {
        $this->doTestFile(__DIR__ . '/fixtures/negative.php.inc');
    }

    public function testUnrelatedNamedConstructorArgumentDoesNotBlockRename(): void
    {
        $this->doTestFile(__DIR__ . '/fixtures/unrelated_constructor.php.inc');
    }

    public function testKeepsNamedCallsToTheConstructor(): void
    {
        $this->doTestFile(__DIR__ . '/fixtures/own_named_constructor.php.inc');
    }

    public function testKeepsNamedCallsToAnInheritedConstructor(): void
    {
        $this->doTestFile(__DIR__ . '/fixtures/inherited_named_constructor.php.inc');
    }

    public function testDifferentMethodArgumentsKeepTheirOwnNames(): void
    {
        $this->doTestFile(__DIR__ . '/fixtures/unrelated_methods.php.inc');
    }

    public function testMatchingMethodCallsStillBlockTheRename(): void
    {
        $this->doTestFile(__DIR__ . '/fixtures/matching_method.php.inc');
    }

    public function testReflectionForwardingStillBlocksTheRename(): void
    {
        $this->doTestFile(__DIR__ . '/fixtures/forwarding_method.php.inc');
    }

    public function testDynamicNamedCallsStillBlockTheRename(): void
    {
        $this->doTestFile(__DIR__ . '/fixtures/dynamic_named_call.php.inc');
    }

    public function testSkipsDynamicVariableAndTraitPropertyUses(): void
    {
        $this->doTestFile(__DIR__ . '/fixtures/guarded.php.inc');
    }
}
