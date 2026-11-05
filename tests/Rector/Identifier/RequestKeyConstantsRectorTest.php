<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\Rector\Identifier;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class RequestKeyConstantsRectorTest extends AbstractRectorTestCase
{
    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/request.php';
    }

    public function testFormRequestRulesCallsAndHeader(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/request.php.inc', includeFixtureDirectoryAsSource: true);
    }

    public function testExistingConstantsRemainUnchanged(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/request-idempotent.php.inc', includeFixtureDirectoryAsSource: true);
    }
}
