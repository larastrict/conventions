<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan;

use LaraStrict\Conventions\PHPStan\ViewDataRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ViewDataRule>
 */
final class ViewDataRuleTest extends RuleTestCase
{
    private bool $enabled = true;

    protected function getRule(): Rule
    {
        return new ViewDataRule(enabled: $this->enabled);
    }

    public function testDisabledRuleAllowsViolations(): void
    {
        $this->enabled = false;

        $this->analyse([__DIR__ . '/Fixtures/Views/BadViewData.php'], []);
    }

    public function testAcceptsViewEntityDataForApplicationViews(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Views/InvoiceViewEntity.php', __DIR__ . '/Fixtures/Views/GoodViewData.php', __DIR__ . '/Fixtures/Views/InvoicePage.php'], []);
    }

    public function testRejectsMissingInvalidAndNullableViewData(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Views/InvoiceViewEntity.php', __DIR__ . '/Fixtures/Views/BadViewData.php'], [
            ['Application views must receive a data array with a typed Entity under the data key.', 18],
            ['Application views must receive a data array with a typed Entity under the data key.', 19],
            ['Application view data must contain a typed Entity from Entities or an HTTP endpoint namespace under the data key; extra values must be scalar.', 20],
            ['Application view data must contain a typed Entity from Entities or an HTTP endpoint namespace under the data key; extra values must be scalar.', 23],
            ['Application view data must contain a typed Entity from Entities or an HTTP endpoint namespace under the data key; extra values must be scalar.', 24],
            ['Application view data must contain a typed Entity from Entities or an HTTP endpoint namespace under the data key; extra values must be scalar.', 27],
            ['Application view data must contain a typed Entity from Entities or an HTTP endpoint namespace under the data key; extra values must be scalar.', 30],
        ]);
    }

    public function testEntityFolderDoesNotReplaceRequiredClassSuffix(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Views/UnsuffixedViewData.php'], [
            ['Application view data must contain a typed Entity from Entities or an HTTP endpoint namespace under the data key; extra values must be scalar.', 11],
        ]);
    }

    public function testHttpEndpointEntityCanBePassedToAView(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Views/Http/TenantApi/Login/LoginEntity.php',
            __DIR__ . '/Fixtures/Views/HttpEndpointViewData.php',
        ], []);
    }
}
