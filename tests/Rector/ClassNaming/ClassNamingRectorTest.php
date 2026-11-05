<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\Rector\ClassNaming;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class ClassNamingRectorTest extends AbstractRectorTestCase
{
    public function testRenamesDeclarationsAndReferences(): void
    {
        $this->doTestFile(__DIR__ . '/fixtures/scope.php.inc');
    }

    public function testMovesNamespace(): void
    {
        $this->doTestFile(__DIR__ . '/fixtures/placement.php.inc');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config.php';
    }
}
