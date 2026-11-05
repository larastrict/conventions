<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan;

use LaraStrict\Conventions\PHPStan\RequestKeyConstantsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RequestKeyConstantsRule>
 */
final class RequestKeyConstantsRuleTest extends RuleTestCase
{
    /**
     * @var list<string>
     */
    private array $applicationNamespaces = ['App'];

    private bool $enabled = true;

    protected function getRule(): Rule
    {
        return new RequestKeyConstantsRule($this->applicationNamespaces, enabled: $this->enabled);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/request-keys.neon'];
    }

    public function testDisabledRuleAllowsViolations(): void
    {
        $this->enabled = false;

        $this->analyse([__DIR__ . '/Fixtures/Requests/BadRequest.php'], []);
    }

    public function testConstantKeysComposedPathsAndValidationRuleNamesAreAllowed(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Requests/Support/GetValue.php', __DIR__ . '/Fixtures/Requests/GoodRequest.php'], []);
    }

    public function testLiteralValidationAndInputKeysAreReported(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Requests/Support/GetValue.php', __DIR__ . '/Fixtures/Requests/BadRequest.php'], [
            ["Use a request Key* constant instead of the literal key 'name'.", 15],
            ["Use a request Key* constant instead of the literal key '.*.label'.", 16],
            ["Use a request Key* constant instead of the literal key 'name'.", 22],
            ["Use a request Key* constant instead of the literal key 'page'.", 23],
            ["Use a request Key* constant instead of the literal key 'items'.", 24],
            ["Use a request Key* constant instead of the literal key 'items'.", 25],
            ["Use a request Key* constant instead of the literal key 'name'.", 27],
        ]);
    }

    public function testOtherClassesAreNotTreatedAsRequests(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Requests/OtherService.php'], []);
    }

    public function testUnconfiguredNamespacesAreIgnored(): void
    {
        $this->applicationNamespaces = ['AnotherApplication'];
        $this->analyse([__DIR__ . '/Fixtures/Requests/BadRequest.php'], []);
    }
}
