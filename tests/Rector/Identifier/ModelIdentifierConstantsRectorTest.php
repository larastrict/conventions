<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\Rector\Identifier;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class ModelIdentifierConstantsRectorTest extends AbstractRectorTestCase
{
    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/model.php';
    }

    public function testModelDefinitionsAndSupportedCalls(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/model.php.inc', includeFixtureDirectoryAsSource: true);
    }

    public function testModelAndRelationDeclarationsDoNotDuplicateConstantsWhenCombined(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/combined.php.inc', includeFixtureDirectoryAsSource: true);
    }

    public function testExistingConstantsRemainUnchanged(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/model-idempotent.php.inc', includeFixtureDirectoryAsSource: true);
    }

    public function testRelationshipMethodsGetConstantsOnInterfaceAndModelOnce(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/relations.php.inc', includeFixtureDirectoryAsSource: true);
    }

    public function testExistingRelationshipConstantsRemainUnchanged(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/relations-idempotent.php.inc', includeFixtureDirectoryAsSource: true);
    }

    public function testQueryAliasesAreExtractedFromLiteralAndInterpolatedSql(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/query-aliases.php.inc', includeFixtureDirectoryAsSource: true);
    }

    public function testQueryAliasesLeaveFirstClassCallablesUnchanged(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/query-alias-callables.php.inc', includeFixtureDirectoryAsSource: true);
    }
}
