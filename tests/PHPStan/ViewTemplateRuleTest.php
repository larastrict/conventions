<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan;

use Illuminate\Filesystem\Filesystem;
use LaraStrict\Conventions\PHPStan\Services\ViewTemplateService;
use LaraStrict\Conventions\PHPStan\ViewTemplateRule;
use PhpParser\ParserFactory;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ViewTemplateRule>
 */
final class ViewTemplateRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ViewTemplateRule(new ViewTemplateService(new Filesystem(), new ParserFactory()));
    }

    public function testReportsMissingEntityTypeAndArrayAccessInBladeTemplate(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Views/BadTemplate.blade.php'], [
            ['Declare $data with @var and its domain *Entity class type.', 1],
            ['Use $data->property instead of $data[...] for view data.', 1],
        ]);
    }

    public function testAcceptsEntityTypedPropertyAccessInBladeTemplate(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Views/GoodTemplate.blade.php'], []);
    }
}
