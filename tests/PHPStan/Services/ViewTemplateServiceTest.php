<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Services;

use Illuminate\Filesystem\Filesystem;
use LaraStrict\Conventions\PHPStan\Services\ViewTemplateService;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

final class ViewTemplateServiceTest extends TestCase
{
    private const string Type = '@php /** @var \\App\\Order\\Entities\\OrderPageViewEntity $data */ @endphp' . "\n";

    private ViewTemplateService $viewTemplateService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewTemplateService = new ViewTemplateService(new Filesystem(), new ParserFactory());
    }

    public function testTypedPropertyAccessAndRealListIndexingAreAllowed(): void
    {
        self::assertSame([], $this->viewTemplateService->execute(self::Type . '{{ $data->title }} {{ $data->items[0]->name }}'));
    }

    public function testArrayAccessIsRejectedEvenWhenTypeIsDeclared(): void
    {
        $errors = $this->viewTemplateService->execute(self::Type . '{{ $data["title"] }}');
        self::assertSame(['larastrict.views.arrayAccess'], array_column($errors, 'identifier'));
    }

    public function testMissingOrWrongTypeIsReported(): void
    {
        $checker = $this->viewTemplateService;
        self::assertSame(['larastrict.views.dataType'], array_column($checker->execute('{{ $data->title }}'), 'identifier'));
        self::assertSame(['larastrict.views.dataType'], array_column($checker->execute('@php /** @var array $data */ @endphp {{ $data->title }}'), 'identifier'));
    }

    public function testBoundComponentDataIsInspectedWithoutResolvingTheComponent(): void
    {
        $checker = $this->viewTemplateService;
        self::assertSame([], $checker->execute(self::Type . '<x-Unknown::summary :data="$data->summary" />'));
        self::assertSame(['larastrict.views.arrayAccess'], array_column($checker->execute(self::Type . '<x-Unknown::summary :title="$data[\'title\']" />'), 'identifier'));
    }

    public function testCommentsAndPlainTextAreNotPHPArrayAccess(): void
    {
        self::assertSame([], $this->viewTemplateService->execute('{{-- {{ $data["name"] }} --}} Plain text: $data["name"]'));
    }

    public function testComponentPropsAreNotMistakenForDataArrays(): void
    {
        self::assertSame([], $this->viewTemplateService->execute("@props(['data'])" . self::Type . '{{ $data->title }}'));
    }

    public function testMalformedTemplateReturnsACompileFinding(): void
    {
        $errors = $this->viewTemplateService->execute('{{ $data-> }}');
        self::assertSame(['larastrict.views.syntax'], array_column($errors, 'identifier'));
    }

    public function testHttpEndpointEntitiesCanBeUsedAsTypedViewData(): void
    {
        $template = str_replace('\\App\\Order\\Entities\\OrderPageViewEntity', '\\App\\Order\\Http\\TenantApi\\Login\\LoginEntity', self::Type);
        $checker = $this->viewTemplateService;
        self::assertSame([], $checker->execute($template . '{{ $data->title }}'));
        self::assertSame(['larastrict.views.arrayAccess'], array_column($checker->execute($template . '{{ $data["title"] }}'), 'identifier'));
    }

    public function testStateDoesNotLeakAfterIncompleteBlocksOrParserErrors(): void
    {
        self::assertSame([], $this->viewTemplateService->execute(self::Type . '@forelse($data->items as $item){{ $item->name }}@empty Empty @endforelse'));
        self::assertSame(['larastrict.views.syntax'], array_column($this->viewTemplateService->execute('@forelse($data->items as $item){{ $item->name }}'), 'identifier'));
        self::assertSame([], $this->viewTemplateService->execute('@verbatim {{ $data["ignored"] }} @endverbatim'));
        self::assertSame(['larastrict.views.dataType', 'larastrict.views.arrayAccess'], array_column($this->viewTemplateService->execute('{{ $data["name"] }}'), 'identifier'));
        self::assertSame([], $this->viewTemplateService->execute(self::Type . '{{ $data->name }}'));
    }

    public function testRootMustBeOneNamedEntityTypeFromAnAllowedNamespace(): void
    {
        $checker = $this->viewTemplateService;
        foreach (['\\App\\Order\\Entities\\OrderPage', 'list<\\App\\Order\\Entities\\OrderPageViewEntity>', '\\App\\Order\\Entities\\OrderPageViewEntity|null', '\\App\\Order\\Models\\OrderPageViewEntity', '\\App\\Order\\Http\\Controllers\\OrderPageViewEntity', '\\App\\Order\\Http\\TenantApi\\Requests\\OrderPageViewEntity', '\\App\\Order\\Http\\TenantApi\\Login\\LoginPage'] as $invalidType) {
            $template = str_replace('\\App\\Order\\Entities\\OrderPageViewEntity', $invalidType, self::Type);
            self::assertSame(['larastrict.views.dataType'], array_column($checker->execute($template . '{{ $data->title }}'), 'identifier'));
        }
    }
}
